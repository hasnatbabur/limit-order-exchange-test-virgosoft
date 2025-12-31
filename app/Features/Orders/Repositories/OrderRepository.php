<?php

namespace App\Features\Orders\Repositories;

use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use App\Features\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function all(): Collection
    {
        return Order::all();
    }

    public function findById(int $id): ?Order
    {
        return Order::find($id);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order;
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    /**
     * Find all orders for a user, ordered by creation date (newest first).
     */
    public function findByUserId(int $userId): Collection
    {
        return Order::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    public function findOpenOrders(): Collection
    {
        return Order::open()->get();
    }

    /**
     * Find all open orders for a symbol, ordered by price, limited to specified count.
     */
    public function findOpenOrdersBySymbol(string $symbol, int $limit = 20): Collection
    {
        return Order::open()->forSymbol($symbol)->orderByPrice()->limit($limit)->get();
    }

    /**
     * Find all open buy orders for a symbol, ordered by price (highest first), limited to specified count.
     */
    public function findOpenBuyOrdersBySymbol(string $symbol, int $limit = 20): Collection
    {
        return Order::open()->buy()->forSymbol($symbol)->orderBy('price', 'desc')->limit($limit)->get();
    }

    /**
     * Find all open sell orders for a symbol, ordered by price (lowest first), limited to specified count.
     */
    public function findOpenSellOrdersBySymbol(string $symbol, int $limit = 20): Collection
    {
        return Order::open()->sell()->forSymbol($symbol)->orderBy('price', 'asc')->limit($limit)->get();
    }

    public function findFirstMatchingBuyOrder(string $symbol, float $price): ?Order
    {
        return Order::open()
            ->buy()
            ->forSymbol($symbol)
            ->where('price', '>=', $price)
            ->orderBy('price', 'desc')
            ->first();
    }

    public function findFirstMatchingSellOrder(string $symbol, float $price): ?Order
    {
        return Order::open()
            ->sell()
            ->forSymbol($symbol)
            ->where('price', '<=', $price)
            ->orderBy('price', 'asc')
            ->first();
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['status' => $status->value]);
        return $order;
    }

    public function incrementFilledAmount(Order $order, float $amount): Order
    {
        return DB::transaction(function () use ($order, $amount) {
            $order->increment('filled_amount', $amount);
            $order->refresh();

            // Check if order is completely filled
            if ($order->isCompletelyFilled()) {
                $order->update(['status' => OrderStatus::FILLED->value]);
            }

            return $order;
        });
    }
}
