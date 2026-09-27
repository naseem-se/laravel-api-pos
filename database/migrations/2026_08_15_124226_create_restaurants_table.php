<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('address')->nullable();

            // Flexible, rarely-queried display/config fields stay JSON.
            // Anything that needs to be queried/filtered (subscription
            // status, expiry) is a real column below, not buried in JSON.
            $table->json('settings')->nullable();

            $table->string('subscription_plan')->default('free');
            $table->enum('subscription_status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamp('subscription_start_date')->nullable();
            $table->timestamp('subscription_end_date')->nullable();
            $table->unsignedInteger('max_branches')->default(1);
            $table->unsignedInteger('max_orders_per_month')->nullable(); // null = unlimited

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};