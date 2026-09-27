<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price_monthly' => ['sometimes', 'required', 'numeric', 'min:0'],
            'max_branches' => ['sometimes', 'required', 'integer', 'min:1'],
            'max_orders_per_month' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
        // slug is deliberately NOT updatable — Restaurant.subscription_plan
        // stores it as a string reference; changing a plan's slug after
        // restaurants are already on it would silently orphan them.
    }
}