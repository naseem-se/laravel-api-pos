<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    use BelongsToRestaurant;

    protected $table = 'tables';

    protected $fillable = [
        'restaurant_id', 'branch_id', 'table_number', 'capacity', 'status',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DiningTable $table) {
            if (! $table->qr_token) {
                $table->qr_token = static::generateUniqueToken();
            }
        });
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (static::allRestaurants()->where('qr_token', $token)->exists());

        return $token;
    }

    public function regenerateQrToken(): void
    {
        $this->update(['qr_token' => static::generateUniqueToken()]);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}