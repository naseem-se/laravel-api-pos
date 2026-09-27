<?php

use App\Http\Controllers\Api\PrinterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    Route::get('printers', [PrinterController::class, 'index']);
    Route::get('printers/{printer}/print-job', [PrinterController::class, 'printJob']);

    Route::middleware('role:owner')->group(function () {
        Route::post('printers', [PrinterController::class, 'store']);
        Route::put('printers/{printer}', [PrinterController::class, 'update']);
        Route::delete('printers/{printer}', [PrinterController::class, 'destroy']);
    });
});