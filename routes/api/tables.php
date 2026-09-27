<?php

use App\Http\Controllers\Api\TableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    Route::get('tables', [TableController::class, 'index']);

    Route::middleware('role:owner|cashier,sanctum')->group(function () {
        Route::patch('tables/{table}/status', [TableController::class, 'updateStatus']);
    });

    Route::middleware('role:owner,sanctum')->group(function () {
        Route::post('tables', [TableController::class, 'store']);
        Route::put('tables/{table}', [TableController::class, 'update']);
        Route::delete('tables/{table}', [TableController::class, 'destroy']);
        Route::get('tables/{table}/qr-code', [TableController::class, 'qrCode']);
        Route::post('tables/{table}/qr-code/regenerate', [TableController::class, 'regenerateQr']);
    });
});