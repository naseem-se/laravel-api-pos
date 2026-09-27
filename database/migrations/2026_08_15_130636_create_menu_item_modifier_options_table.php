<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_modifier_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained('menu_item_modifier_groups')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price_delta', 10, 2)->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('modifier_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_modifier_options');
    }
};