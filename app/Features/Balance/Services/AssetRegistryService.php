<?php

namespace App\Features\Balance\Services;

use Illuminate\Support\Collection;

class AssetRegistryService
{
    /**
     * Get all supported assets configuration.
     *
     * @return array
     */
    public function getSupportedAssets(): array
    {
        return config('assets.supported_assets', []);
    }

    /**
     * Check if an asset symbol is supported.
     *
     * @param string $symbol
     * @return bool
     */
    public function isAssetSupported(string $symbol): bool
    {
        $symbol = strtoupper($symbol);
        $supportedAssets = $this->getSupportedAssets();

        return isset($supportedAssets[$symbol]) && $supportedAssets[$symbol]['enabled'];
    }

    /**
     * Get configuration for a specific asset.
     *
     * @param string $symbol
     * @return array|null
     */
    public function getAssetConfig(string $symbol): ?array
    {
        $symbol = strtoupper($symbol);
        $supportedAssets = $this->getSupportedAssets();

        return $supportedAssets[$symbol] ?? null;
    }

    /**
     * Get all enabled assets.
     *
     * @return Collection
     */
    public function getEnabledAssets(): Collection
    {
        $supportedAssets = $this->getSupportedAssets();

        return collect($supportedAssets)
            ->filter(fn($config) => $config['enabled'])
            ->keys();
    }

    /**
     * Get default assets for new users.
     *
     * @return array
     */
    public function getDefaultAssets(): array
    {
        $defaultAssets = config('assets.default_assets', []);
        $enabledAssets = $this->getEnabledAssets();

        return array_intersect($defaultAssets, $enabledAssets->toArray());
    }

    /**
     * Check if auto-creation is enabled.
     *
     * @return bool
     */
    public function isAutoCreateEnabled(): bool
    {
        return config('assets.auto_create', false);
    }

    /**
     * Validate asset amount against configuration.
     *
     * @param string $symbol
     * @param float $amount
     * @return bool
     */
    public function validateAssetAmount(string $symbol, float $amount): bool
    {
        $config = $this->getAssetConfig($symbol);

        if (!$config) {
            return false;
        }

        $minAmount = (float) $config['min_amount'];

        return $amount >= $minAmount;
    }

    /**
     * Get decimal places for an asset.
     *
     * @param string $symbol
     * @return int
     */
    public function getAssetDecimalPlaces(string $symbol): int
    {
        $config = $this->getAssetConfig($symbol);

        return $config['decimal_places'] ?? 8;
    }

    /**
     * Get asset name for display.
     *
     * @param string $symbol
     * @return string
     */
    public function getAssetName(string $symbol): string
    {
        $config = $this->getAssetConfig($symbol);

        return $config['name'] ?? $symbol;
    }
}
