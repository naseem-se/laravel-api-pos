<?php

namespace App\Http\Requests\Purchasing;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'payment_method' => ['sometimes', 'required', 'in:cash,bank_transfer,card,other'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:150'],
            'expense_date' => ['sometimes', 'required', 'date'],
            'is_recurring' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}