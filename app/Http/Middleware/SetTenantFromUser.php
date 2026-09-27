<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromUser
{
    // Runs after auth:sanctum, so $request->user() is guaranteed
    // available. Superadmin has no restaurant_id — Tenant stays
    // unset for them, meaning every global scope above is a no-op,
    // which is exactly the "see everything" behavior superadmin needs.
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->restaurant_id) {
            Tenant::set($user->restaurant_id);
        }

        return $next($request);
    }
}