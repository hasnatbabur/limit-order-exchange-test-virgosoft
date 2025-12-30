<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue.js SPA for the root path
Route::get('/', fn () => view('app'))->name('app');

// Serve the Vue.js SPA for all other routes except API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api\/).*');
