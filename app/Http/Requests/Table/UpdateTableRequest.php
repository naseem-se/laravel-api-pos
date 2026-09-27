<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->has('branch_id') && ! $this->filled('branch_id')) {
            $this->merge(['branch_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'table_number' => ['sometimes', 'required', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }
}