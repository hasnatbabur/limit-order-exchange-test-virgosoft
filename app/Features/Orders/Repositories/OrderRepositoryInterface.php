<?php

namespace App\Features\Orders\Repositories;

use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use App\Features\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Order;
    public function create(array $data): Order;
    public function update(Order $order, array $data): Order;
    public function delete(Order $order): bool;
    public function findByUserId(int $userId): Collection;
    public function findOpenOrders(): Collection;
    public function findOpenOrdersBySymbol(string $symbol): Collection;
    public function findOpenBuyOrdersBySymbol(string $symbol): Collection;
    public function findOpenSellOrdersBySymbol(string $symbol): Collection;
    public function findFirstMatchingBuyOrder(string $symbol, float $price): ?Order;
    public function findFirstMatchingSellOrder(string $symbol, float $price): ?Order;
    public function updateStatus(Order $order, OrderStatus $status): Order;
    public function incrementFilledAmount(Order $order, float $amount): Order;
}
