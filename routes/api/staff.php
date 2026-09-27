<?php

use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription', 'role:owner'])->group(function () {
    Route::get('staff', [StaffController::class, 'index']);
    Route::post('staff', [StaffController::class, 'store']);
    Route::put('staff/{staff}', [StaffController::class, 'update']);
    Route::patch('staff/{staff}/status', [StaffController::class, 'toggleStatus']);
    Route::delete('staff/{staff}', [StaffController::class, 'destroy']);
});