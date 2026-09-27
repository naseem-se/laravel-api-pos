<?php

use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    Route::get('menu-categories', [MenuCategoryController::class, 'index']);

    Route::middleware('role:owner')->group(function () {
        Route::post('menu-categories', [MenuCategoryController::class, 'store']);
        Route::put('menu-categories/{menu_category}', [MenuCategoryController::class, 'update']);
        Route::delete('menu-categories/{menu_category}', [MenuCategoryController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    Route::get('menu-items', [MenuItemController::class, 'index']);

    Route::middleware('role:owner')->group(function () {
        Route::post('menu-items', [MenuItemController::class, 'store']);
        Route::put('menu-items/{menu_item}', [MenuItemController::class, 'update']);
        Route::delete('menu-items/{menu_item}', [MenuItemController::class, 'destroy']);
    });

    Route::middleware('role:owner|cashier')->group(function () {
        Route::patch('menu-items/{menu_item}/availability', [MenuItemController::class, 'toggleAvailability']);
    });
});