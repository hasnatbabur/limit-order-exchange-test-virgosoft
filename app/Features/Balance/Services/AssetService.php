<?php

namespace App\Features\Balance\Services;

use App\Features\Balance\Repositories\AssetRepositoryInterface;
use App\Features\Balance\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AssetService
{
    protected AssetRepositoryInterface $assetRepository;

    public function __construct(AssetRepositoryInterface $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get all assets for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserAssets(int $userId): Collection
    {
        return $this->assetRepository->getUserAssets($userId);
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
        return $this->assetRepository->getUserAssetBySymbol($userId, $symbol);
    }

    /**
     * Get user's complete balance information including USD and assets.
     *
     * @param int $userId
     * @return array
     */
    public function getUserCompleteBalance(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $assets = $this->getUserAssets($userId);
        $totalAssetValue = $this->assetRepository->getUserTotalAssetValue($userId);

        return [
            'user_id' => $userId,
            'usd_balance' => $user->balance,
            'assets' => $assets->map(fn($asset) => $asset->toApiArray()),
            'total_asset_value' => $totalAssetValue,
            'total_portfolio_value' => $user->balance + $totalAssetValue,
        ];
    }

    /**
     * Deposit funds to user's USD balance.
     *
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public function depositUsd(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $amount) {
            $user = User::where('id', $userId)->lockForUpdate()->first();

            if (!$user) {
                return false;
            }

            return $user->addBalance($amount);
        });
    }

    /**
     * Withdraw funds from user's USD balance.
     *
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public function withdrawUsd(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $amount) {
            $user = User::where('id', $userId)->lockForUpdate()->first();

            if (!$user || $user->balance < $amount) {
                return false;
            }

            return $user->subtractBalance($amount);
        });
    }

    /**
     * Deposit cryptocurrency assets to user's account.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function depositAsset(int $userId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->assetRepository->addAssetAmount($userId, $symbol, $amount);
    }

    /**
     * Withdraw cryptocurrency assets from user's account.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function withdrawAsset(int $userId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->assetRepository->subtractAssetAmount($userId, $symbol, $amount);
    }

    /**
     * Lock assets for a sell order.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function lockAssetsForSellOrder(int $userId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $symbol, $amount) {
            $asset = $this->getUserAssetBySymbol($userId, $symbol);

            if (!$asset || !$asset->hasAvailableAmount($amount)) {
                return false;
            }

            return $this->assetRepository->lockAssetAmount($asset, $amount);
        });
    }

    /**
     * Unlock assets from a cancelled sell order.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function unlockAssetsFromCancelledOrder(int $userId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $symbol, $amount) {
            $asset = $this->getUserAssetBySymbol($userId, $symbol);

            if (!$asset || $asset->locked_amount < $amount) {
                return false;
            }

            return $this->assetRepository->unlockAssetAmount($asset, $amount);
        });
    }

    /**
     * Execute asset transfer for a fulfilled trade.
     *
     * @param int $buyerId
     * @param int $sellerId
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function executeAssetTransfer(int $buyerId, int $sellerId, string $symbol, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($buyerId, $sellerId, $symbol, $amount) {
            // Get seller's asset and convert locked to available
            $sellerAsset = $this->getUserAssetBySymbol($sellerId, $symbol);
            if (!$sellerAsset || $sellerAsset->locked_amount < $amount) {
                return false;
            }

            $sellerSuccess = $this->assetRepository->convertLockedToAvailable($sellerAsset, $amount);
            if (!$sellerSuccess) {
                return false;
            }

            // Add assets to buyer
            return $this->assetRepository->addAssetAmount($buyerId, $symbol, $amount);
        });
    }

    /**
     * Check if user has sufficient USD balance.
     *
     * @param int $userId
     * @param float $requiredAmount
     * @return bool
     */
    public function hasSufficientUsdBalance(int $userId, float $requiredAmount): bool
    {
        $user = User::find($userId);
        return $user && $user->balance >= $requiredAmount;
    }

    /**
     * Check if user has sufficient available assets.
     *
     * @param int $userId
     * @param string $symbol
     * @param float $requiredAmount
     * @return bool
     */
    public function hasSufficientAvailableAssets(int $userId, string $symbol, float $requiredAmount): bool
    {
        $asset = $this->getUserAssetBySymbol($userId, $symbol);
        return $asset && $asset->hasAvailableAmount($requiredAmount);
    }

    /**
     * Get user's portfolio summary.
     *
     * @param int $userId
     * @return array
     */
    public function getPortfolioSummary(int $userId): array
    {
        $balance = $this->getUserCompleteBalance($userId);

        return [
            'total_value' => $balance['total_portfolio_value'],
            'usd_balance' => $balance['usd_balance'],
            'asset_count' => $balance['assets']->count(),
            'total_asset_value' => $balance['total_asset_value'],
            'assets_breakdown' => $balance['assets']->map(function ($asset) {
                return [
                    'symbol' => $asset['symbol'],
                    'total_amount' => $asset['amount'],
                    'available_amount' => $asset['available_amount'],
                    'locked_amount' => $asset['locked_amount'],
                ];
            }),
        ];
    }

    /**
     * Initialize default assets for a new user.
     *
     * @param int $userId
     * @return void
     */
    public function initializeUserAssets(int $userId): void
    {
        // Create default asset entries for common cryptocurrencies
        $defaultAssets = ['BTC', 'ETH'];

        foreach ($defaultAssets as $symbol) {
            $this->assetRepository->createOrUpdateAsset($userId, $symbol, 0.0, 0.0);
        }
    }

    /**
     * Validate asset symbol.
     *
     * @param string $symbol
     * @return bool
     */
    public function isValidAssetSymbol(string $symbol): bool
    {
        $validSymbols = ['BTC', 'ETH', 'USDT', 'USDC'];
        return in_array(strtoupper($symbol), $validSymbols);
    }
}
