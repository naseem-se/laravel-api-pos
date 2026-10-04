<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug', 'name', 'price_monthly', 'currency',
        'max_branches', 'max_orders_per_month', 'features', 'display_order', 'is_active',
        'kds_enabled', 'online_ordering_enabled',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'kds_enabled' => 'boolean',
            'online_ordering_enabled' => 'boolean',
        ];
    }
}