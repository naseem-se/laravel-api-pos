<?php

use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PurchasingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription', 'role:owner'])->group(function () {
    Route::get('inventory/summary', [InventoryController::class, 'summary']);
    Route::get('inventory/items', [InventoryController::class, 'index']);
    Route::post('inventory/items', [InventoryController::class, 'store']);
    Route::put('inventory/items/{inventoryItem}', [InventoryController::class, 'update']);
    Route::delete('inventory/items/{inventoryItem}', [InventoryController::class, 'destroy']);
    Route::post('inventory/items/{inventoryItem}/adjustments', [InventoryController::class, 'adjust']);
    Route::get('inventory/items/{inventoryItem}/transactions', [InventoryController::class, 'transactions']);
    Route::get('inventory/menu-items/{menuItem}/recipe', [InventoryController::class, 'recipe']);
    Route::put('inventory/menu-items/{menuItem}/recipe', [InventoryController::class, 'updateRecipe']);

    Route::get('suppliers', [PurchasingController::class, 'suppliers']);
    Route::post('suppliers', [PurchasingController::class, 'storeSupplier']);
    Route::put('suppliers/{supplier}', [PurchasingController::class, 'updateSupplier']);

    Route::get('purchases/summary', [PurchasingController::class, 'summary']);
    Route::get('purchases', [PurchasingController::class, 'index']);
    Route::post('purchases', [PurchasingController::class, 'store']);
    Route::get('purchases/{purchase}', [PurchasingController::class, 'show']);
    Route::put('purchases/{purchase}', [PurchasingController::class, 'update']);
    Route::post('purchases/{purchase}/receive', [PurchasingController::class, 'receive']);
    Route::post('purchases/{purchase}/cancel', [PurchasingController::class, 'cancel']);
    Route::post('purchases/{purchase}/payments', [PurchasingController::class, 'payment']);
    Route::delete('purchases/{purchase}', [PurchasingController::class, 'destroy']);

    Route::get('expenses/summary', [ExpenseController::class, 'summary']);
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
});