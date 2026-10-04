<?php

namespace App\Http\Requests\Printer;

use Illuminate\Foundation\Http\FormRequest;

class StorePrinterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'in:receipt,kitchen,both'],
            'connection_type' => ['required', 'in:network,system'],
            'output_mode' => ['sometimes', 'required', 'in:escpos,driver_text'],
            'ip' => ['nullable', 'required_if:connection_type,network', 'ip'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'system_printer_name' => ['nullable', 'required_if:connection_type,system', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }
}