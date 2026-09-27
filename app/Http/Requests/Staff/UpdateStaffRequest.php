<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
        $staffId = $this->route('staff')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staffId)],
            'role' => ['sometimes', 'required', 'in:cashier,kitchen'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! $this->hasAny(['name', 'email', 'role', 'branch_id']) && ! $this->exists('branch_id')) {
                $v->errors()->add('body', 'Make at least one change before saving.');
            }
        });
    }
}