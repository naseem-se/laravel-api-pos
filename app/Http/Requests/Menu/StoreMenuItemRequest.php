<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->has('category_id') && ! $this->has('menu_category_id')) {
            $this->merge(['menu_category_id' => $this->input('category_id')]);
        }

        if ($this->has('preparation_time_minutes') && ! $this->filled('preparation_time_minutes')) {
            $this->merge(['preparation_time_minutes' => null]);
        }
        if ($this->has('image_url') && ! $this->filled('image_url')) {
            $this->merge(['image_url' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'menu_category_id' => ['required', 'integer', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:0'],

            'modifier_groups' => ['nullable', 'array'],
            'modifier_groups.*.name' => ['required_with:modifier_groups', 'string', 'max:255'],
            'modifier_groups.*.selection_type' => ['required_with:modifier_groups', 'in:single,multiple'],
            'modifier_groups.*.pricing_mode' => ['required_with:modifier_groups', 'in:additive,override'],
            'modifier_groups.*.is_required' => ['nullable', 'boolean'],
            'modifier_groups.*.options' => ['required_with:modifier_groups', 'array', 'min:1'],
            'modifier_groups.*.options.*.name' => ['required', 'string', 'max:255'],
            'modifier_groups.*.options.*.price_delta' => ['required', 'numeric'],

            'recipe_items' => ['nullable', 'array'],
            'recipe_items.*.inventory_item_id' => ['required_with:recipe_items', 'integer', 'exists:inventory_items,id'],
            'recipe_items.*.quantity_consumed' => ['required_with:recipe_items', 'numeric', 'min:0.001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('modifier_groups', []) as $index => $group) {
                if (($group['pricing_mode'] ?? null) === 'override' && ($group['selection_type'] ?? null) === 'multiple') {
                    $validator->errors()->add(
                        "modifier_groups.{$index}",
                        "'{$group['name']}': this price option allows customers to choose only one item"
                    );
                }
            }
        });
    }
}