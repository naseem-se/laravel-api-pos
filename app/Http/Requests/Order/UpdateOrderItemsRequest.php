<?php

namespace App\Http\Requests\Order;

class UpdateOrderItemsRequest extends StoreOrderRequest
{
    public function rules(): array
    {
        return [
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