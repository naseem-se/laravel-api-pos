<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // nullOnDelete, not cascade/restrict: a menu item being
            // deleted later must never corrupt or block deletion of
            // historical orders — the name/price below are already a
            // frozen snapshot, so the FK losing its target is harmless.
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('name'); // snapshot — survives menu item edits/deletion
            $table->decimal('price', 10, 2); // snapshot of base price at order time
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 10, 2); // (base price + modifier deltas) * quantity
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};