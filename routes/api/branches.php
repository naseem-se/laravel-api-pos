<?php

use App\Http\Controllers\Api\BranchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    Route::get('branches', [BranchController::class, 'index']); // any staff role — needed for branch dropdowns

    Route::middleware('role:owner')->group(function () {
        Route::post('branches', [BranchController::class, 'store']);
        Route::put('branches/{branch}', [BranchController::class, 'update']);
        Route::delete('branches/{branch}', [BranchController::class, 'destroy']);
    });
});