<?php

namespace App\Http\Requests\Inventory;

class UpdateInventoryItemRequest extends StoreInventoryItemRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'unit' => ['sometimes', 'required', 'string', 'max:30'],
            'low_stock_threshold' => ['sometimes', 'required', 'numeric', 'min:0'],
            'cost_per_unit' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}