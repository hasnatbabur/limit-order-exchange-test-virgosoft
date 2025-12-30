<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use App\Http\Controllers\Auth\RegisteredUserController;

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

// Protected authentication routes
Route::middleware('auth')->group(function () {
    Route::get('/auth/me', [AuthenticatedUserController::class, 'show']);
    Route::post('/auth/logout', [AuthenticatedUserController::class, 'logout']);
});
