<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue.js SPA for all routes except API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api\/).*');
