<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $sku
 * @property string $unit
 * @property string $current_stock
 * @property string $low_stock_threshold
 * @property string $cost_per_unit
 * @property bool $is_active
 */
class InventoryItem extends Model
{
    use BelongsToRestaurant;
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id', 'sku', 'name', 'category', 'unit',
        'current_stock', 'low_stock_threshold', 'cost_per_unit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:4',
            'low_stock_threshold' => 'decimal:4',
            'cost_per_unit' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_inventory')
            ->withPivot('quantity_per_item')
            ->withTimestamps();
    }
}