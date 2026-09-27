<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Services\RestaurantSettingsService;
use App\Traits\ApiResponse;

class RestaurantSettingsController extends Controller
{
    use ApiResponse;

    public function __construct(protected RestaurantSettingsService $settingsService) {}

    public function show()
    {
        return $this->success(new RestaurantResource($this->settingsService->get()));
    }

    public function update(UpdateRestaurantRequest $request)
    {
        $restaurant = $this->settingsService->update($request->validated());

        return $this->success(new RestaurantResource($restaurant));
    }

    public function toggleStatus()
    {
        $restaurant = $this->settingsService->toggleStatus();

        return $this->success(new RestaurantResource($restaurant));
    }
}