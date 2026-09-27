<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Plan;
use App\Models\Restaurant;

class PlanManagementService
{
    public function listAll()
    {
        return Plan::orderBy('display_order')->get();
    }

    public function create(array $data): Plan
    {
        return Plan::create($data);
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan;
    }

    public function delete(Plan $plan): void
    {
        $inUseCount = Restaurant::allRestaurants()->where('subscription_plan', $plan->slug)->count();

        if ($inUseCount > 0) {
            throw ApiException::conflict(
                "{$inUseCount} restaurant(s) are currently on this plan. Deactivate it instead, or move them to a different plan first."
            );
        }

        $plan->delete();
    }
}