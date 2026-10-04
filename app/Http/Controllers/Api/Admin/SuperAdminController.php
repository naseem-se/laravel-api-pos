<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPlanRequest;
use App\Http\Requests\Admin\RenewSubscriptionRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\SubscriptionService;
use App\Services\SuperAdminService;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function __construct(
        protected SuperAdminService $superAdminService,
        protected SubscriptionService $subscriptionService,
    ) {}

    public function stats()
    {
        return response()->json(['success' => true, 'stats' => $this->superAdminService->platformStats()]);
    }

    public function restaurants(Request $request)
    {
        $result = $this->superAdminService->listRestaurants($request->only(['search', 'plan', 'status', 'page', 'limit']));

        return response()->json([
            'success' => true,
            'restaurants' => RestaurantResource::collection($result['restaurants']),
            'total_count' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
        ]);
    }

    public function restaurantDetail(int $id)
    {
        $restaurant = Restaurant::allRestaurants()->findOrFail($id);

        return response()->json(['success' => true, 'restaurant' => new RestaurantResource($restaurant)]);
    }

    public function toggleStatus(Request $request, int $id)
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        $restaurant = Restaurant::allRestaurants()->findOrFail($id);
        $restaurant = $this->superAdminService->toggleRestaurantStatus($restaurant, $request->boolean('is_active'));

        return response()->json(['success' => true, 'restaurant' => new RestaurantResource($restaurant)]);
    }

    public function destroy(int $id)
    {
        $restaurant = Restaurant::allRestaurants()->findOrFail($id);
        $this->superAdminService->deleteRestaurant($restaurant);

        return response()->json(['success' => true, 'message' => 'Restaurant and associated data deleted']);
    }

    public function changePlan(AdminPlanRequest $request, int $id)
    {
        $restaurant = Restaurant::allRestaurants()->findOrFail($id);
        $restaurant = $this->subscriptionService->applyPlan($restaurant, $request->plan);

        return response()->json(['success' => true, 'restaurant' => new RestaurantResource($restaurant)]);
    }

    public function renew(RenewSubscriptionRequest $request, int $id)
    {
        $restaurant = Restaurant::allRestaurants()->findOrFail($id);
        $restaurant = $this->superAdminService->renewSubscription($restaurant, $request->integer('days', 30));

        return response()->json(['success' => true, 'restaurant' => new RestaurantResource($restaurant)]);
    }
}