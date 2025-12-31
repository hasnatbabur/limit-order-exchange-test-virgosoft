<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BroadcastAuthController;

// Serve the Vue.js SPA for the root path
Route::get('/', fn () => view('app'))->name('app');

// Add login route for authentication middleware
Route::get('/login', fn () => view('app'))->name('login');

// Custom broadcasting authentication route
Route::match(['GET', 'POST'], '/broadcasting/auth', [BroadcastAuthController::class, 'authenticate']);

// Serve the Vue.js SPA for all other routes except API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api\/).*');
