<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/menu.php';
    require __DIR__.'/api/branches.php';
    require __DIR__.'/api/tables.php';
    require __DIR__.'/api/orders.php';
    require __DIR__.'/api/subscription.php';
    require __DIR__.'/api/staff.php';
    require __DIR__.'/api/restaurant.php';
    require __DIR__.'/api/reports.php';
    require __DIR__.'/api/admin.php';
    require __DIR__.'/api/printers.php';
    require __DIR__.'/api/inventory.php';
    require __DIR__.'/api/dashboard.php';
    require __DIR__.'/api/uploads.php';
});