<?php

namespace App\Features\Balance\Repositories;

use App\Features\Balance\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AssetRepository implements AssetRepositoryInterface
{
    /**
     * Get all assets for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserAssets(int $userId): Collection
    {
        return Asset::forUser($userId)->get();
    }

    /**
     * Get a specific asset for a user by symbol.
     *
     * @param int $userId
     * @param string $symbol
     * @return Asset|null
     */
    public function getUserAssetBySymbol(int $userId, string $symbol): ?Asset
    {
        return Asset::forUser($userId)->bySymbol($symbol)->first();
    }

    /**
     * Create or update an asset for a user.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @param float $lockedAmount
     * @return Asset
     */
    public function createOrUpdateAsset(int $userId, string $symbol, float $amount = 0.0, float $lockedAmount = 0.0): Asset
    {
        return Asset::updateOrCreate(
            ['user_id' => $userId, 'symbol' => $symbol],
            [
                'amount' => $amount,
                'locked_amount' => $lockedAmount,
            ]
        );
    }

    /**
     * Update asset amounts atomically.
     *
     * @param Asset $asset
     * @param float $newAmount
     * @param float $newLockedAmount
     * @return bool
     */
    public function updateAssetAmounts(Asset $asset, float $newAmount, float $newLockedAmount): bool
    {
        return $asset->updateAmounts($newAmount, $newLockedAmount);
    }

    /**
     * Get assets with available balance for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserAssetsWithAvailableBalance(int $userId): Collection
    {
        return Asset::forUser($userId)->withAvailableBalance()->get();
    }

    /**
     * Get all assets in the system (for admin purposes).
     *
     * @return Collection
     */
    public function allAssets(): Collection
    {
        return Asset::all();
    }

    /**
     * Find asset by ID.
     *
     * @param int $id
     * @return Asset|null
     */
    public function findById(int $id): ?Asset
    {
        return Asset::find($id);
    }

    /**
     * Delete an asset.
     *
     * @param Asset $asset
     * @return bool
     */
    public function delete(Asset $asset): bool
    {
        return $asset->delete();
    }

    /**
     * Get total value of all assets for a user.
     * Note: This is a placeholder implementation. In a real system,
     * you would integrate with price data from external APIs.
     *
     * @param int $userId
     * @return float
     */
    public function getUserTotalAssetValue(int $userId): float
    {
        // This is a simplified implementation
        // In production, you would fetch current market prices
        $assets = $this->getUserAssets($userId);
        $totalValue = 0.0;

        foreach ($assets as $asset) {
            // Placeholder prices - in production, fetch from market data API
            $price = match($asset->symbol) {
                'BTC' => 45000.0, // Placeholder BTC price
                'ETH' => 3000.0,  // Placeholder ETH price
                default => 1.0,   // Default price for other assets
            };

            $totalValue += $asset->amount * $price;
        }

        return $totalValue;
    }

    /**
     * Lock assets for sell orders with atomic operation.
     *
     * @param Asset $asset
     * @param float $amount
     * @return bool
     */
    public function lockAssetAmount(Asset $asset, float $amount): bool
    {
        return DB::transaction(function () use ($asset, $amount) {
            // Lock the asset row for update to prevent race conditions
            $lockedAsset = Asset::where('id', $asset->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAsset || !$lockedAsset->hasAvailableAmount($amount)) {
                return false;
            }

            return $lockedAsset->lockAmount($amount);
        });
    }

    /**
     * Unlock assets from sell orders with atomic operation.
     *
     * @param Asset $asset
     * @param float $amount
     * @return bool
     */
    public function unlockAssetAmount(Asset $asset, float $amount): bool
    {
        return DB::transaction(function () use ($asset, $amount) {
            // Lock the asset row for update to prevent race conditions
            $lockedAsset = Asset::where('id', $asset->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAsset || $lockedAsset->locked_amount < $amount) {
                return false;
            }

            return $lockedAsset->unlockAmount($amount);
        });
    }

    /**
     * Convert locked assets to available (for order fulfillment) with atomic operation.
     *
     * @param Asset $asset
     * @param float $amount
     * @return bool
     */
    public function convertLockedToAvailable(Asset $asset, float $amount): bool
    {
        return DB::transaction(function () use ($asset, $amount) {
            // Lock the asset row for update to prevent race conditions
            $lockedAsset = Asset::where('id', $asset->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAsset || $lockedAsset->locked_amount < $amount) {
                return false;
            }

            return $lockedAsset->convertLockedToAvailable($amount);
        });
    }

    /**
     * Add funds to user asset with atomic operation.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function addAssetAmount(int $userId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $symbol, $amount) {
            $asset = $this->getUserAssetBySymbol($userId, $symbol);

            if (!$asset) {
                // Create new asset if it doesn't exist
                $asset = $this->createOrUpdateAsset($userId, $symbol, $amount, 0.0);
                return $asset->exists;
            }

            // Lock the asset row for update
            $lockedAsset = Asset::where('id', $asset->id)
                ->lockForUpdate()
                ->first();

            return $lockedAsset ? $lockedAsset->addAmount($amount) : false;
        });
    }

    /**
     * Subtract funds from user asset with atomic operation.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function subtractAssetAmount(int $userId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $symbol, $amount) {
            $asset = $this->getUserAssetBySymbol($userId, $symbol);

            if (!$asset) {
                return false;
            }

            // Lock the asset row for update
            $lockedAsset = Asset::where('id', $asset->id)
                ->lockForUpdate()
                ->first();

            return $lockedAsset ? $lockedAsset->subtractAmount($amount) : false;
        });
    }
}
