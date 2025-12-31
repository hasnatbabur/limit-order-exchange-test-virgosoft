<?php

namespace App\Features\Orders\Services;

use App\Features\Balance\Services\AssetService;
use App\Features\Balance\Events\BalanceUpdated;
use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use App\Features\Orders\Events\OrderBookUpdated;
use App\Features\Orders\Events\OrderMatched;
use App\Features\Orders\Events\OrderCancelled;
use App\Features\Orders\Models\Order;
use App\Features\Orders\Models\Trade;
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

            // Get updated user data for broadcasting
            $user = User::find($order->user_id);
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => $user->balance,
            ];

            // Broadcast order cancellation
            broadcast(new OrderCancelled($cancelledOrder, $userData));

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
     * Get paginated user's orders with optional filters.
     */
    public function getPaginatedUserOrders(User $user, array $filters = [], int $perPage = 10): \Illuminate\Pagination\LengthAwarePaginator
    {
        \Log::info('Getting paginated orders', [
            'user_id' => $user->id,
            'filters' => $filters,
            'per_page' => $perPage
        ]);

        $result = $this->orderRepository->findPaginatedByUserId($user->id, $filters, $perPage);

        \Log::info('Paginated orders result', [
            'total' => $result->total(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage()
        ]);

        return $result;
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

        // Create trade record
        $trade = Trade::create([
            'buy_order_id' => $newOrder->side === OrderSide::BUY ? $newOrder->id : $matchingOrder->id,
            'sell_order_id' => $newOrder->side === OrderSide::SELL ? $newOrder->id : $matchingOrder->id,
            'symbol' => $newOrder->symbol,
            'price' => $tradePrice,
            'amount' => $tradeAmount,
            'commission' => $commission,
        ]);

        // Determine which order is buy and which is sell
        $buyOrder = $newOrder->side === OrderSide::BUY ? $newOrder : $matchingOrder;
        $sellOrder = $newOrder->side === OrderSide::SELL ? $newOrder : $matchingOrder;

        // Process the trade
        $this->processTrade($buyOrder, $sellOrder, $tradeAmount, $tradePrice, $commission, $trade);

        // Get user data for broadcasting
        $buyer = User::find($buyOrder->user_id);
        $seller = User::find($sellOrder->user_id);

        // Broadcast order matched event
        \Log::info('Broadcasting OrderMatched event', [
            'trade_id' => $trade->id,
            'symbol' => $trade->symbol,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id
        ]);

        broadcast(new OrderMatched(
            $trade,
            [
                'id' => $buyOrder->id,
                'symbol' => $buyOrder->symbol,
                'side' => $buyOrder->side->value,
                'price' => number_format($buyOrder->price, 8, '.', ''),
                'amount' => number_format($buyOrder->amount, 8, '.', ''),
                'filled_amount' => number_format($buyOrder->filled_amount, 8, '.', ''),
                'status' => $buyOrder->status->value,
            ],
            [
                'id' => $sellOrder->id,
                'symbol' => $sellOrder->symbol,
                'side' => $sellOrder->side->value,
                'price' => number_format($sellOrder->price, 8, '.', ''),
                'amount' => number_format($sellOrder->amount, 8, '.', ''),
                'filled_amount' => number_format($sellOrder->filled_amount, 8, '.', ''),
                'status' => $sellOrder->status->value,
            ],
            [
                'id' => $buyer->id,
                'name' => $buyer->name,
                'email' => $buyer->email,
                'balance' => $buyer->balance,
            ],
            [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
                'balance' => $seller->balance,
            ]
        ));
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

        \Log::info('Broadcasting OrderBookUpdated', [
            'symbol' => $symbol,
            'buy_orders_count' => $orderBook['buy_orders']->count(),
            'sell_orders_count' => $orderBook['sell_orders']->count()
        ]);

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
