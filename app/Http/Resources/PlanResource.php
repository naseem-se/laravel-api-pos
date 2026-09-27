<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price_monthly' => (float) $this->price_monthly,
            'currency' => $this->currency,
            'max_branches' => $this->max_branches,
            'max_orders_per_month' => $this->max_orders_per_month,
            'features' => $this->features ?? [],
        ];
    }
}