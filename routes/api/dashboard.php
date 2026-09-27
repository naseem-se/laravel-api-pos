<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    // All authenticated staff roles can view — cashier and kitchen
    // benefit from seeing today's volume too, not just the owner.
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
});