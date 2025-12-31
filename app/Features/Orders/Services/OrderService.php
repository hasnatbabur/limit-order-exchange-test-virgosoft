<?php

namespace App\Features\Orders\Services;

use App\Features\Balance\Services\AssetService;
use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use App\Features\Orders\Events\OrderBookUpdated;
use App\Features\Orders\Models\Order;
use App\Features\Orders\Repositories\OrderRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected OrderRepositoryInterface $orderRepository;
    protected AssetService $assetService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        AssetService $assetService
    ) {
        $this->orderRepository = $orderRepository;
        $this->assetService = $assetService;
    }

    /**
     * Create a new order.
     */
    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $orderData = array_merge($data, [
                'user_id' => $user->id,
                'status' => OrderStatus::OPEN->value,
                'filled_amount' => 0,
            ]);

            $order = $this->orderRepository->create($orderData);

            // Handle balance/asset locking based on order side
            if ($order->side === OrderSide::BUY) {
                // For buy orders, lock USD balance
                $totalCost = $order->price * $order->amount;
                if (!$this->assetService->lockUsdForBuyOrder($user->id, $totalCost)) {
                    throw new \Exception('Insufficient USD balance');
                }
            } else {
                // For sell orders, lock the asset amount
                $symbol = $this->extractAssetSymbol($order->symbol);
                if (!$this->assetService->lockAssetsForSellOrder($user->id, $symbol, $order->amount)) {
                    throw new \Exception('Insufficient asset balance');
                }
            }

            // Attempt to match the order with existing counter orders
            $this->processOrderMatching($order);

            // Broadcast order book update
            $this->broadcastOrderBookUpdate($order->symbol);

            return $order;
        });
    }

    /**
     * Cancel an order and release locked funds/assets.
     */
    public function cancelOrder(Order $order): Order
    {
        if (!$order->status->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled');
        }

        return DB::transaction(function () use ($order) {
            // Release locked funds/assets
            if ($order->side === OrderSide::BUY) {
                $totalCost = $order->price * $order->amount;
                $this->assetService->unlockUsdFromCancelledOrder($order->user_id, $totalCost);
            } else {
                $symbol = $this->extractAssetSymbol($order->symbol);
                $this->assetService->unlockAssetsFromCancelledOrder($order->user_id, $symbol, $order->amount);
            }

            $cancelledOrder = $this->orderRepository->updateStatus($order, OrderStatus::CANCELLED);

            // Broadcast order book update
            $this->broadcastOrderBookUpdate($order->symbol);

            return $cancelledOrder;
        });
    }

    /**
     * Get order book for a symbol.
     */
    public function getOrderBook(string $symbol, int $limit = 20): array
    {
        return [
            'buy_orders' => $this->orderRepository->findOpenBuyOrdersBySymbol($symbol, $limit),
            'sell_orders' => $this->orderRepository->findOpenSellOrdersBySymbol($symbol, $limit),
        ];
    }

    /**
     * Get user's orders.
     */
    public function getUserOrders(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderRepository->findByUserId($user->id);
    }

    /**
     * Process order matching for a newly created order.
     */
    private function processOrderMatching(Order $order): void
    {
        // Continue matching while the order has remaining amount and is still open
        while ($order->status === OrderStatus::OPEN && $order->remaining_amount > 0) {
            $matchingOrder = null;

            if ($order->side === OrderSide::BUY) {
                // For buy orders, find the lowest priced sell order that meets our price
                $matchingOrder = $this->orderRepository->findFirstMatchingSellOrder(
                    $order->symbol,
                    $order->price
                );
            } else {
                // For sell orders, find the highest priced buy order that meets our price
                $matchingOrder = $this->orderRepository->findFirstMatchingBuyOrder(
                    $order->symbol,
                    $order->price
                );
            }

            // If no matching order found, exit the loop
            if (!$matchingOrder) {
                break;
            }

            // Execute the match
            $this->executeOrderMatch($order, $matchingOrder);
        }
    }

    /**
     * Execute a match between two orders.
     */
    private function executeOrderMatch(Order $newOrder, Order $matchingOrder): void
    {
        // Determine the trade amount (minimum of remaining amounts)
        $tradeAmount = min($newOrder->remaining_amount, $matchingOrder->remaining_amount);
        $tradePrice = $matchingOrder->price; // Use the price of the existing order
        $tradeValue = $tradeAmount * $tradePrice;

        // Calculate commission (1.5% of trade value)
        $commission = $tradeValue * 0.015;

        // Update filled amounts for both orders
        $this->orderRepository->incrementFilledAmount($newOrder, $tradeAmount);
        $this->orderRepository->incrementFilledAmount($matchingOrder, $tradeAmount);

        // Refresh orders to get updated status
        $newOrder->refresh();
        $matchingOrder->refresh();

        // Process the trade
        $this->processTrade($newOrder, $matchingOrder, $tradeAmount, $tradePrice, $commission);
    }

    /**
     * Process the trade by updating balances and assets.
     */
    private function processTrade(Order $buyOrder, Order $sellOrder, float $amount, float $price, float $commission): void
    {
        $tradeValue = $amount * $price;
        $symbol = $this->extractAssetSymbol($buyOrder->symbol);

        // For the buyer (buyOrder)
        // Release locked USD
        $this->assetService->releaseLockedUsdForTrade($buyOrder->user_id, $tradeValue);
        // Add the purchased assets
        $this->assetService->addAssetsFromTrade($buyOrder->user_id, $symbol, $amount);

        // For the seller (sellOrder)
        // Release locked assets
        $this->assetService->releaseLockedAssetsForTrade($sellOrder->user_id, $symbol, $amount);
        // Add USD from the sale (minus commission)
        $this->assetService->addUsdFromTrade($sellOrder->user_id, $tradeValue - $commission);
        // Deduct commission from seller (the one receiving USD)
        $this->assetService->deductCommission($sellOrder->user_id, $commission);
    }

    /**
     * Broadcast order book update for a symbol.
     */
    private function broadcastOrderBookUpdate(string $symbol): void
    {
        $orderBook = $this->getOrderBook($symbol);

        broadcast(new OrderBookUpdated(
            $symbol,
            $orderBook['buy_orders']->toArray(),
            $orderBook['sell_orders']->toArray()
        ));
    }

    /**
     * Extract asset symbol from trading pair (e.g., BTC-USD -> BTC).
     */
    private function extractAssetSymbol(string $tradingPair): string
    {
        return explode('-', $tradingPair)[0];
    }
}
