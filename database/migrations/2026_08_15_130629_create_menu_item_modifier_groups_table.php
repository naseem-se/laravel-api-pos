<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('selection_type', ['single', 'multiple'])->default('single');
            $table->enum('pricing_mode', ['additive', 'override'])->default('additive');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_modifier_groups');
    }
};