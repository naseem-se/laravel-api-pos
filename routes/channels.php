<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurant.{restaurantId}', function ($user, int $restaurantId) {
    if ($user->hasRole('superadmin')) {
        return true; // superadmin can observe any restaurant's channel
    }

    return $user->restaurant_id === $restaurantId;
});