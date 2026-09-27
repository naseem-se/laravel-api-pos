<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOT tenant-scoped — plans are a platform-level concept
        // shared across every restaurant, managed exclusively by
        // superadmin. No restaurant_id column, no BelongsToRestaurant.
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price_monthly', 10, 2);
            $table->string('currency')->default('PKR');
            $table->unsignedInteger('max_branches')->default(1);
            $table->unsignedInteger('max_orders_per_month')->nullable(); // null = unlimited
            $table->json('features')->nullable(); // display bullets, e.g. ["5 branches", "Unlimited orders"]
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};