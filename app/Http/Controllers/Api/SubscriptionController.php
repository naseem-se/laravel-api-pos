<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function changePlan(Request $request)
    {
        $request->validate(['plan' => ['required', 'string', 'exists:plans,slug']]);

        $restaurant = Restaurant::allRestaurants()->find(Tenant::id());
        $restaurant = $this->subscriptionService->applyPlan($restaurant, $request->plan);

        return $this->success(new RestaurantResource($restaurant));
    }
}