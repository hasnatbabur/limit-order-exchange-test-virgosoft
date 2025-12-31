<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Assets Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the supported cryptocurrency assets in the
    | trading system. New assets can be added here without code changes.
    |
    */

    'supported_assets' => [
        'BTC' => [
            'name' => 'Bitcoin',
            'decimal_places' => 8,
            'min_amount' => '0.00000001',
            'enabled' => true,
        ],
        'ETH' => [
            'name' => 'Ethereum',
            'decimal_places' => 8,
            'min_amount' => '0.00000001',
            'enabled' => true,
        ],
        'USDT' => [
            'name' => 'Tether',
            'decimal_places' => 6,
            'min_amount' => '0.000001',
            'enabled' => false, // Can be enabled when needed
        ],
        'USDC' => [
            'name' => 'USD Coin',
            'decimal_places' => 6,
            'min_amount' => '0.000001',
            'enabled' => false, // Can be enabled when needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Creation Settings
    |--------------------------------------------------------------------------
    |
    | Controls whether assets should be automatically created when first
    | accessed. This ensures fault tolerance for end-user experience.
    |
    */

    'auto_create' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Assets for New Users
    |--------------------------------------------------------------------------
    |
    | Assets that should be automatically created for new users during
    | registration. Only enabled assets will be created.
    |
    */

    'default_assets' => ['BTC', 'ETH'],
];
