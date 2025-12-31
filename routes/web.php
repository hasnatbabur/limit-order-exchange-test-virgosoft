<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue.js SPA for the root path
Route::get('/', fn () => view('app'))->name('app');

// Add login route for authentication middleware
Route::get('/login', fn () => view('app'))->name('login');

// Serve the Vue.js SPA for all other routes except API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api\/).*');
