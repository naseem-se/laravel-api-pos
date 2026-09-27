<?php

namespace App\Http\Requests\Printer;

class UpdatePrinterRequest extends StorePrinterRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['purpose'] = ['sometimes', 'required', 'in:receipt,kitchen,both'];
        $rules['connection_type'] = ['sometimes', 'required', 'in:network,system'];
        $rules['is_active'] = ['nullable', 'boolean'];

        return $rules;
    }
}