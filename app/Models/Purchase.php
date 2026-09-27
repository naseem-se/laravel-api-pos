<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'branch_id', 'supplier_id', 'created_by_user_id',
        'purchase_number', 'supplier_invoice_number', 'status', 'purchase_date',
        'subtotal_amount', 'tax_amount', 'discount_amount', 'total_amount',
        'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'received_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }
}