<?php

namespace App\Models\Concerns;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;

// Apply this trait to any model that belongs to a single restaurant
// (Branch, MenuCategory, MenuItem, Table, Order, Customer, etc.).
// Two things happen automatically, with no per-query effort required
// anywhere in the app:
//   1. Every query is filtered to Tenant::id() — SELECT, UPDATE, DELETE
//      all silently scope to the current restaurant. A controller
//      cannot "forget" the filter the way a Node repository method
//      could forget to pass restaurantId.
//   2. Every INSERT auto-fills restaurant_id if it wasn't set
//      explicitly, so `MenuItem::create([...])` just works without
//      the caller needing to remember to pass it.
trait BelongsToRestaurant
{
    protected static function bootBelongsToRestaurant(): void
    {
        static::addGlobalScope('restaurant', function (Builder $builder) {
            if (Tenant::check()) {
                $builder->where($builder->getModel()->getTable().'.restaurant_id', Tenant::id());
            }
        });

        static::creating(function ($model) {
            if (! $model->restaurant_id && Tenant::check()) {
                $model->restaurant_id = Tenant::id();
            }
        });
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Escape hatch for superadmin contexts that genuinely need to
    // query across every restaurant (platform stats, restaurant
    // listing) — explicit and named, so it's obvious at the call site
    // that tenant scoping is intentionally being bypassed.
    public function scopeAllRestaurants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('restaurant');
    }
}