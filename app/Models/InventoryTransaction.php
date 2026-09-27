<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'inventory_item_id', 'branch_id', 'type',
        'quantity_change', 'unit_cost', 'balance_after', 'reference_type',
        'reference_id', 'notes', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'balance_after' => 'decimal:4',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}