<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_url,
            'address' => $this->address,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'settings' => $this->settings,
            'subscription' => [
                'plan' => $this->subscription_plan,
                'status' => $this->subscription_status,
                'start_date' => $this->subscription_start_date,
                'end_date' => $this->subscription_end_date,
                'max_branches' => $this->max_branches,
                'max_orders_per_month' => $this->max_orders_per_month,
            ],
            'is_active' => $this->is_active,
            'created_at' => $this->created_at
        ];
    }
}