<?php

namespace App\Features\Orders\Services;

use App\Features\Balance\Services\AssetService;
use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
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

            return $this->orderRepository->updateStatus($order, OrderStatus::CANCELLED);
        });
    }

    /**
     * Get order book for a symbol.
     */
    public function getOrderBook(string $symbol): array
    {
        return [
            'buy_orders' => $this->orderRepository->findOpenBuyOrdersBySymbol($symbol),
            'sell_orders' => $this->orderRepository->findOpenSellOrdersBySymbol($symbol),
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
     * Extract asset symbol from trading pair (e.g., BTC-USD -> BTC).
     */
    private function extractAssetSymbol(string $tradingPair): string
    {
        return explode('-', $tradingPair)[0];
    }
}
