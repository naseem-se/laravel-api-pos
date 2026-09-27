<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\User;

class SuperAdminService
{
    public function platformStats(): array
    {
        $totalRestaurants = Restaurant::allRestaurants()->count();
        $activeRestaurants = Restaurant::allRestaurants()->where('is_active', true)->count();
        $newThisMonth = Restaurant::allRestaurants()->where('created_at', '>=', now()->startOfMonth())->count();

        $planCounts = Restaurant::allRestaurants()
            ->selectRaw('subscription_plan, COUNT(*) as count')
            ->groupBy('subscription_plan')
            ->pluck('count', 'subscription_plan');

        $planBreakdown = Plan::orderBy('display_order')->get()->map(fn ($plan) => [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'count' => (int) ($planCounts[$plan->slug] ?? 0),
        ]);

        $totalOrders = Order::allRestaurants()->count();
        $totalRevenue = Order::allRestaurants()->where('status', 'completed')->sum('total_amount');

        return [
            'total_restaurants' => $totalRestaurants,
            'active_restaurants' => $activeRestaurants,
            'inactive_restaurants' => $totalRestaurants - $activeRestaurants,
            'new_this_month' => $newThisMonth,
            'plan_breakdown' => $planBreakdown,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
        ];
    }

    public function listRestaurants(array $filters): array
    {
        $query = Restaurant::allRestaurants()
            ->withCount(['users as staff_count', 'orders as order_count' => fn ($q) => $q])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['plan'] ?? null, fn ($q, $v) => $q->where('subscription_plan', $v))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false));

        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 20);

        $total = $query->count();
        $restaurants = $query->orderByDesc('created_at')->forPage($page, $limit)->get();

        return ['restaurants' => $restaurants, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function toggleRestaurantStatus(Restaurant $restaurant, bool $isActive): Restaurant
    {
        $restaurant->update(['is_active' => $isActive]);

        return $restaurant->fresh();
    }

    public function renewSubscription(Restaurant $restaurant, int $days = 30): Restaurant
    {
        $restaurant->update([
            'subscription_status' => 'active',
            'subscription_end_date' => now()->addDays($days),
        ]);

        return $restaurant->fresh();
    }
}