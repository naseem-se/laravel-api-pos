<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'logo_url' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],

            'tax_rate_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'accepting_online_orders' => ['sometimes', 'boolean'],
            'receipt_paper_width' => ['sometimes', 'in:58mm,80mm'],

            'loyalty' => ['sometimes', 'array'],
            'loyalty.enabled' => ['sometimes', 'boolean'],
            'loyalty.earn_rate_per_100_spent' => ['sometimes', 'numeric', 'min:0'],
            'loyalty.redemption_value_per_point' => ['sometimes', 'numeric', 'min:0'],
            'loyalty.min_points_to_redeem' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}