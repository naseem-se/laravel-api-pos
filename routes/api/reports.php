<?php

use App\Http\Controllers\Api\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription', 'role:owner'])->group(function () {
    Route::get('reports/summary', [ReportsController::class, 'summary']);
    Route::get('reports/export', [ReportsController::class, 'export']);
});