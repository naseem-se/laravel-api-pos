<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItemModifierGroup extends Model
{
    protected $fillable = [
        'menu_item_id', 'name', 'selection_type', 'pricing_mode', 'is_required', 'display_order',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(MenuItemModifierOption::class, 'modifier_group_id')->orderBy('display_order');
    }
}