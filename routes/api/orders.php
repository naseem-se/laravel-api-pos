<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    // Read access for every authenticated role — kitchen needs the
    // list/detail for KDS, same as the Node build.
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    Route::middleware('role:owner|cashier,sanctum')->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
        Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel']);
        Route::put('orders/{order}/items', [OrderController::class, 'updateItems']);
    });

    Route::middleware('role:owner|cashier|kitchen,sanctum')->group(function () {
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
});