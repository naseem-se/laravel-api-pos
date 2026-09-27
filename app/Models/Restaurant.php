<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Restaurant extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo_url', 'contact_phone', 'contact_email', 'address',
        'settings', 'subscription_plan', 'subscription_status',
        'subscription_start_date', 'subscription_end_date',
        'max_branches', 'max_orders_per_month', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'subscription_start_date' => 'datetime',
            'subscription_end_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Sensible defaults so `settings` is never null when read, even
    // for a freshly-created restaurant that hasn't touched Settings yet.
    protected $attributes = [
        'settings' => '{"currency":"PKR","timezone":"Asia/Karachi","taxRatePercent":0,"acceptingOnlineOrders":false,"receiptPaperWidth":"80mm","loyalty":{"enabled":false,"earnRatePer100Spent":0,"redemptionValuePerPoint":0,"minPointsToRedeem":0}}',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Restaurant $restaurant) {
            if (! $restaurant->slug) {
                $restaurant->slug = static::generateUniqueSlug($restaurant->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_status === 'cancelled') {
            return false;
        }

        if ($this->subscription_end_date && $this->subscription_end_date->isPast()) {
            return false;
        }

        return true;
    }

    public function scopeAllRestaurants(Builder $query): Builder
    {
        return $query;
    }
}