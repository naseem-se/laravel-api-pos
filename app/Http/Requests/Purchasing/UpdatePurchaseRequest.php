<?php

namespace App\Http\Requests\Purchasing;

class UpdatePurchaseRequest extends StorePurchaseRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => ['sometimes', 'nullable', 'integer', 'exists:suppliers,id'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'supplier_invoice_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'purchase_date' => ['sometimes', 'required', 'date'],
            'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required_with:items', 'integer', 'distinct', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}