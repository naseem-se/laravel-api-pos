<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasRole('superadmin') && Tenant::check()) {
            $restaurant = Restaurant::allRestaurants()->find(Tenant::id());

            if (! $restaurant || ! $restaurant->is_active) {
                abort(403, 'This restaurant account is suspended.');
            }

            $this->subscriptionService->ensureSubscriptionActive($restaurant);
        }

        return $next($request);
    }
}