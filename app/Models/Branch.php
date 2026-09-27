<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'name', 'address', 'phone', 'is_main_branch', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main_branch' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}