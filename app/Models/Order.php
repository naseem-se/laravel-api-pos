<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property int|null $placed_by_user_id
 * @property int $order_number
 * @property int|null $table_id
 * @property-read DiningTable|null $table
 * @property string $order_type
 * @property string $status
 * @property string $discount_amount
 * @property string|null $customer_name
 * @property string|null $notes
 */
class Order extends Model
{
    use BelongsToRestaurant;

    // The state machine, in one place — every legal transition. This
    // is the direct equivalent of the Node build's ORDER_STATUS_FLOW
    // constant; both the service (enforcement) and any future
    // frontend logic should read from a single source of truth like
    // this rather than duplicating the map.
    public const STATUS_FLOW = [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['served', 'cancelled'],
        'served' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'restaurant_id', 'branch_id', 'order_number', 'order_type', 'table_id',
        'customer_name', 'placed_by_user_id', 'status', 'payment_method',
        'subtotal_amount', 'tax_amount', 'discount_amount', 'total_amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('changed_at');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by_user_id');
    }
}