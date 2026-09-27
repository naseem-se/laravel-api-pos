<?php

namespace App\Http\Requests\Menu;

class UpdateMenuItemRequest extends StoreMenuItemRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['menu_category_id'] = ['sometimes', 'required', 'integer', 'exists:menu_categories,id'];
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['price'] = ['sometimes', 'required', 'numeric', 'min:0'];
        $rules['is_available'] = ['nullable', 'boolean'];

        return $rules;
    }
}