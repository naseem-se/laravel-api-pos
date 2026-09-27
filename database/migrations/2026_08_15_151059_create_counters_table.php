<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Not tenant-scoped — the restaurant ID is baked into the
        // 'key' string itself (e.g. "order_number:42"), so this table
        // has no restaurant_id column and doesn't use BelongsToRestaurant.
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedInteger('seq')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};