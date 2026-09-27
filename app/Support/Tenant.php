<?php

namespace App\Support;

// Thread-safe-enough for a single request lifecycle: holds "which
// restaurant is the current authenticated user scoped to," set once
// by SetTenantFromUser middleware near the start of the request, then
// read by every tenant-scoped model's global scope for the rest of
// it. This is the direct equivalent of Node's req.restaurantId, just
// living in a request-scoped singleton instead of being threaded
// through every controller argument.
class Tenant
{
    protected static ?int $restaurantId = null;

    public static function set(?int $restaurantId): void
    {
        static::$restaurantId = $restaurantId;
    }

    public static function id(): ?int
    {
        return static::$restaurantId;
    }

    public static function check(): bool
    {
        return static::$restaurantId !== null;
    }

    public static function clear(): void
    {
        static::$restaurantId = null;
    }
}