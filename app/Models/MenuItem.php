<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property string|null $image_url
 * @property bool $is_available
 */
class MenuItem extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'menu_category_id', 'name', 'description',
        'price', 'image_url', 'is_available', 'preparation_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function modifierGroups(): HasMany
    {
        return $this->hasMany(MenuItemModifierGroup::class)->orderBy('display_order');
    }

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'menu_item_inventory')
            ->withPivot('quantity_per_item')
            ->withTimestamps();
    }
}