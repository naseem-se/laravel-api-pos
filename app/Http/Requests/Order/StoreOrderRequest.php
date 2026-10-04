<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_type' => ['required', 'in:dine-in,takeaway'],
            'table_id' => ['required_if:order_type,dine-in', 'nullable', 'integer', 'exists:tables,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.selected_modifiers' => ['nullable', 'array'],
            'items.*.selected_modifiers.*.group_id' => ['required_with:items.*.selected_modifiers', 'integer'],
            'items.*.selected_modifiers.*.option_id' => ['required_with:items.*.selected_modifiers', 'integer'],
        ];
    }
}