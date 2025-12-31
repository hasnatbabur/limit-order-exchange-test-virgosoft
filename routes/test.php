<?php

use Illuminate\Support\Facades\Route;
use App\Features\Orders\Events\OrderBookUpdated;

Route::get('/test-broadcast', function () {
    // Test broadcasting an event
    broadcast(new OrderBookUpdated(
        'BTC-USD',
        [
            ['id' => 1, 'price' => '50000.00', 'amount' => '0.1', 'total' => '5000.00'],
            ['id' => 2, 'price' => '49000.00', 'amount' => '0.2', 'total' => '9800.00']
        ],
        [
            ['id' => 3, 'price' => '51000.00', 'amount' => '0.15', 'total' => '7650.00'],
            ['id' => 4, 'price' => '52000.00', 'amount' => '0.1', 'total' => '5200.00']
        ]
    ));

    return response()->json(['message' => 'Test broadcast sent']);
});
