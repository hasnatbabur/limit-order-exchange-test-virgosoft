<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::post('/auth/register', [RegisteredUserController::class, 'store']);
Route::post('/auth/login', [AuthenticatedUserController::class, 'login']);
Route::options('/auth/login', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
});

// Password reset routes
Route::post('/auth/password/forgot', [PasswordResetController::class, 'sendResetLink']);
Route::post('/auth/password/reset', [PasswordResetController::class, 'reset']);

// CSRF token endpoint for SPA
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->middleware('web');

// Protected authentication routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthenticatedUserController::class, 'show']);
    Route::post('/auth/logout', [AuthenticatedUserController::class, 'logout']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Order routes
    Route::get('/orders', [App\Features\Orders\Http\Controllers\OrderController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/orders', [App\Features\Orders\Http\Controllers\OrderController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/orderbook', [App\Features\Orders\Http\Controllers\OrderController::class, 'orderBook'])->middleware('throttle:60,1');
    Route::post('/orders/{order}/cancel', [App\Features\Orders\Http\Controllers\OrderController::class, 'cancel'])->middleware('throttle:10,1');

    // Trade routes
    Route::get('/trades', [App\Features\Orders\Http\Controllers\TradeController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/trades/recent', [App\Features\Orders\Http\Controllers\TradeController::class, 'recent'])->middleware('throttle:60,1');

    // Asset routes
    Route::get('/assets', [App\Features\Balance\Http\Controllers\AssetController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/assets/balance', [App\Features\Balance\Http\Controllers\AssetController::class, 'balance'])->middleware('throttle:60,1');
    Route::get('/assets/portfolio', [App\Features\Balance\Http\Controllers\AssetController::class, 'portfolio'])->middleware('throttle:60,1');
    Route::post('/assets/test-add', [App\Features\Balance\Http\Controllers\AssetController::class, 'addTestAssets'])->middleware('throttle:10,1');

    // Test top-up route
    Route::post('/test/topup', function (Request $request) {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $amount = 10000.00;

            // Check if balance column exists
            if (!isset($user->balance)) {
                return response()->json(['error' => 'Balance column not found'], 500);
            }

            $result = $user->addBalance($amount);

            if (!$result) {
                return response()->json(['error' => 'Failed to add balance'], 500);
            }

            // Refresh the user model to get updated balance
            $user->refresh();

            return response()->json([
                'message' => "Successfully added $10,000 to your balance",
                'new_balance' => $user->balance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    })->middleware('throttle:5,1');
});
