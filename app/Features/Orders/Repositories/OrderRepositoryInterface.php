<?php

namespace App\Features\Orders\Repositories;

use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use App\Features\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Order;
    public function create(array $data): Order;
    public function update(Order $order, array $data): Order;
    public function delete(Order $order): bool;
    /**
     * Find all orders for a user, ordered by creation date (newest first).
     */
    public function findByUserId(int $userId): Collection;

    /**
     * Find paginated orders for a user with optional filters.
     */
    public function findPaginatedByUserId(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator;
    public function findOpenOrders(): Collection;
    public function findOpenOrdersBySymbol(string $symbol, int $limit = 20): Collection;
    public function findOpenBuyOrdersBySymbol(string $symbol, int $limit = 20): Collection;
    public function findOpenSellOrdersBySymbol(string $symbol, int $limit = 20): Collection;
    public function findFirstMatchingBuyOrder(string $symbol, float $price): ?Order;
    public function findFirstMatchingSellOrder(string $symbol, float $price): ?Order;
    public function updateStatus(Order $order, OrderStatus $status): Order;
    public function incrementFilledAmount(Order $order, float $amount): Order;
}
