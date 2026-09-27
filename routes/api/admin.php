<?php

use App\Http\Controllers\Api\Admin\AdminPlanController;
use App\Http\Controllers\Api\Admin\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:superadmin,sanctum'])->prefix('admin')->group(function () {
    Route::get('stats', [SuperAdminController::class, 'stats']);
    Route::get('restaurants', [SuperAdminController::class, 'restaurants']);
    Route::get('restaurants/{id}', [SuperAdminController::class, 'restaurantDetail']);
    Route::patch('restaurants/{id}/status', [SuperAdminController::class, 'toggleStatus']);
    Route::patch('restaurants/{id}/plan', [SuperAdminController::class, 'changePlan']);
    Route::patch('restaurants/{id}/renew', [SuperAdminController::class, 'renew']);

    Route::get('plans', [AdminPlanController::class, 'index']);
    Route::post('plans', [AdminPlanController::class, 'store']);
    Route::put('plans/{plan}', [AdminPlanController::class, 'update']);
    Route::delete('plans/{plan}', [AdminPlanController::class, 'destroy']);
});