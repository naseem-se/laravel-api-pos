<?php

use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('plans', [PlanController::class, 'index']);

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::middleware('role:owner,sanctum')->group(function () {
        Route::post('subscription/change-plan', [SubscriptionController::class, 'changePlan']);
    });
});