<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    public function deleteRestaurant(Restaurant $restaurant): void
    {
        DB::transaction(function () use ($restaurant) {
            $restaurantId = $restaurant->id;
            $orderIds = DB::table('orders')->where('restaurant_id', $restaurantId)->select('id');
            $orderItemIds = DB::table('order_items')->whereIn('order_id', $orderIds)->select('id');
            $purchaseIds = DB::table('purchases')->where('restaurant_id', $restaurantId)->select('id');
            $menuItemIds = DB::table('menu_items')->where('restaurant_id', $restaurantId)->select('id');
            $modifierGroupIds = DB::table('menu_item_modifier_groups')->whereIn('menu_item_id', $menuItemIds)->select('id');

            DB::table('order_item_modifiers')->whereIn('order_item_id', $orderItemIds)->delete();
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('order_status_histories')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->where('restaurant_id', $restaurantId)->delete();

            DB::table('purchase_items')->whereIn('purchase_id', $purchaseIds)->delete();
            DB::table('purchase_payments')->where('restaurant_id', $restaurantId)->delete();
            DB::table('purchases')->where('restaurant_id', $restaurantId)->delete();

            DB::table('inventory_transactions')->where('restaurant_id', $restaurantId)->delete();
            DB::table('menu_item_inventory')->where('restaurant_id', $restaurantId)->delete();
            DB::table('menu_item_modifier_options')->whereIn('modifier_group_id', $modifierGroupIds)->delete();
            DB::table('menu_item_modifier_groups')->whereIn('menu_item_id', $menuItemIds)->delete();
            DB::table('menu_items')->where('restaurant_id', $restaurantId)->delete();
            DB::table('menu_categories')->where('restaurant_id', $restaurantId)->delete();

            DB::table('expenses')->where('restaurant_id', $restaurantId)->delete();
            DB::table('tables')->where('restaurant_id', $restaurantId)->delete();
            DB::table('printers')->where('restaurant_id', $restaurantId)->delete();
            DB::table('suppliers')->where('restaurant_id', $restaurantId)->delete();
            DB::table('inventory_items')->where('restaurant_id', $restaurantId)->delete();

            $userIds = DB::table('users')->where('restaurant_id', $restaurantId)->select('id');
            $userEmails = DB::table('users')->where('restaurant_id', $restaurantId)->select('email');
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $userIds)
                ->delete();
            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->whereIn('model_id', $userIds)
                ->delete();
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            DB::table('password_reset_tokens')->whereIn('email', $userEmails)->delete();
            DB::table('users')->where('restaurant_id', $restaurantId)->delete();

            DB::table('branches')->where('restaurant_id', $restaurantId)->delete();

            $restaurant->delete();
        });
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