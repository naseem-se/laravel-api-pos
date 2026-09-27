<?php

use App\Http\Controllers\Api\RestaurantSettingsController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('restaurants/me', [RestaurantSettingsController::class, 'show']);
    Route::put('restaurants/me', [RestaurantSettingsController::class, 'update']);
    Route::patch('restaurants/me/status', [RestaurantSettingsController::class, 'toggleStatus']);
    Route::post('restaurants/me/change-plan', [SubscriptionController::class, 'changePlan']);
});