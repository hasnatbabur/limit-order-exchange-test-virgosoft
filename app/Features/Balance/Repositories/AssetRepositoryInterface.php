<?php

namespace App\Features\Balance\Repositories;

use App\Features\Balance\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AssetRepositoryInterface
{
    /**
     * Get all assets for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserAssets(int $userId): Collection;

    /**
     * Get a specific asset for a user by symbol.
     *
     * @param int $userId
     * @param string $symbol
     * @return Asset|null
     */
    public function getUserAssetBySymbol(int $userId, string $symbol): ?Asset;

    /**
     * Create or update an asset for a user.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @param float $lockedAmount
     * @return Asset
     */
    public function createOrUpdateAsset(int $userId, string $symbol, float $amount = 0.0, float $lockedAmount = 0.0): Asset;

    /**
     * Update asset amounts atomically.
     *
     * @param Asset $asset
     * @param float $newAmount
     * @param float $newLockedAmount
     * @return bool
     */
    public function updateAssetAmounts(Asset $asset, float $newAmount, float $newLockedAmount): bool;

    /**
     * Get assets with available balance for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserAssetsWithAvailableBalance(int $userId): Collection;

    /**
     * Get all assets in the system (for admin purposes).
     *
     * @return Collection
     */
    public function allAssets(): Collection;

    /**
     * Find asset by ID.
     *
     * @param int $id
     * @return Asset|null
     */
    public function findById(int $id): ?Asset;

    /**
     * Delete an asset.
     *
     * @param Asset $asset
     * @return bool
     */
    public function delete(Asset $asset): bool;

    /**
     * Get total value of all assets for a user (if price data is available).
     *
     * @param int $userId
     * @return float
     */
    public function getUserTotalAssetValue(int $userId): float;

    /**
     * Lock assets for sell orders.
     *
     * @param Asset $asset
     * @param float $amount
     * @return bool
     */
    public function lockAssetAmount(Asset $asset, float $amount): bool;

    /**
     * Unlock assets from sell orders.
     *
     * @param Asset $asset
     * @param float $amount
     * @return bool
     */
    public function unlockAssetAmount(Asset $asset, float $amount): bool;

    /**
     * Convert locked assets to available (for order fulfillment).
     *
     * @param Asset $asset
     * @param float $amount
     * @return bool
     */
    public function convertLockedToAvailable(Asset $asset, float $amount): bool;
}
