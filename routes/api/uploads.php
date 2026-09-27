<?php

use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'subscription', 'role:owner'])->group(function () {
    Route::post('uploads/image', [UploadController::class, 'image']);
});