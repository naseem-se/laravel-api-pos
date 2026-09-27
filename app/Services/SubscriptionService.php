<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Support\Tenant;

class SubscriptionService
{
    // Applying a plan is the ONE place that writes the denormalized
    // snapshot fields onto Restaurant — every other read (quota
    // checks, branch limits) goes straight to the live Plan record
    // instead of trusting that snapshot, which is what makes editing
    // a plan's limits take effect immediately for existing restaurants.
    public function applyPlan(Restaurant $restaurant, string $planSlug): Restaurant
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->first();

        if (! $plan) {
            throw ApiException::badRequest('This plan is not available. Choose another plan.');
        }

        $restaurant->update([
            'subscription_plan' => $plan->slug,
            'subscription_status' => 'active',
            'subscription_start_date' => now(),
            'subscription_end_date' => now()->addDays(30),
        ]);

        return $restaurant->fresh();
    }

    public function renew(Restaurant $restaurant, int $days = 30): Restaurant
    {
        $restaurant->update([
            'subscription_status' => 'active',
            'subscription_end_date' => now()->addDays($days),
        ]);

        return $restaurant->fresh();
    }

    // Called from AuthService::login (Step 3) and the EnsureSubscriptionActive
    // middleware below — the single definition of "is this subscription
    // usable right now," so both entry points can never drift apart.
    public function ensureSubscriptionActive(Restaurant $restaurant): void
    {
        if ($restaurant->subscription_status === 'cancelled') {
            throw ApiException::paymentRequired('This subscription has been cancelled. Contact support to reactivate.');
        }
        if ($restaurant->subscription_end_date && $restaurant->subscription_end_date->isPast()) {
            throw ApiException::paymentRequired('Your subscription has expired. Please contact support to renew.');
        }
    }

    public function getMonthlyOrderCount(int $restaurantId): int
    {
        return Order::allRestaurants()
            ->where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    // THE authoritative order-quota check — called from OrderService's
    // create() as the very first thing that happens, not from
    // middleware. This mirrors the Node build's fix for the exact bug
    // that let orders slip past the limit: middleware-only enforcement
    // means any route that forgets to attach it has silently no limit
    // at all. Calling it from inside the service itself makes it
    // structurally impossible to route around.
    public function assertCanPlaceOrder(int $restaurantId): void
    {
        $restaurant = Restaurant::allRestaurants()->find($restaurantId);

        if (! $restaurant) {
            throw ApiException::notFound('Restaurant not found');
        }

        $this->ensureSubscriptionActive($restaurant);

        $plan = Plan::where('slug', $restaurant->subscription_plan)->first();
        $maxOrdersPerMonth = $plan?->max_orders_per_month;

        if ($maxOrdersPerMonth !== null) {
            $currentCount = $this->getMonthlyOrderCount($restaurantId);

            if ($currentCount >= $maxOrdersPerMonth) {
                throw ApiException::paymentRequired(
                    "Monthly order limit ({$maxOrdersPerMonth}) reached for your current plan. Upgrade to continue."
                );
            }
        }
    }
}