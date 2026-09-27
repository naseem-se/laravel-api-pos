<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: a category with items still assigned
            // can't be hard-deleted at the DB level either — mirrors
            // the Node service's explicit guard, enforced here too as
            // a second line of defense.
            $table->foreignId('menu_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('preparation_time_minutes')->default(10);
            $table->timestamps();

            $table->index(['restaurant_id', 'menu_category_id']);
            $table->index(['restaurant_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};