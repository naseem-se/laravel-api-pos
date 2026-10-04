<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'branch_id', 'name', 'purpose',
        'connection_type', 'output_mode', 'ip', 'port', 'system_printer_name', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}