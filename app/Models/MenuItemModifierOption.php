<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemModifierOption extends Model
{
    protected $fillable = ['modifier_group_id', 'name', 'price_delta', 'display_order'];

    protected function casts(): array
    {
        return ['price_delta' => 'decimal:2'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MenuItemModifierGroup::class, 'modifier_group_id');
    }
}