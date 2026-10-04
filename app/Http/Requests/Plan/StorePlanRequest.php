<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', 'unique:plans,slug'],
            'name' => ['required', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'max_branches' => ['required', 'integer', 'min:1'],
            'max_orders_per_month' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'kds_enabled' => ['sometimes', 'boolean'],
            'online_ordering_enabled' => ['sometimes', 'boolean'],
        ];
    }
}