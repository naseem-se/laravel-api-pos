<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Public, rate-limited under the 'auth' limiter (5/min) defined
    // in Step 1's bootstrap/app.php — the actual brute-force target.
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register-restaurant', [AuthController::class, 'registerRestaurant']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password/{token}', [AuthController::class, 'resetPassword']);
    });

    // Authenticated — tenant middleware included even though these
    // specific endpoints don't need tenant-scoped queries yet, so the
    // pattern is consistent everywhere auth:sanctum appears.
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
    });
});