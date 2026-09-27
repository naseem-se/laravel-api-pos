<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('table_number');
            $table->unsignedInteger('capacity')->default(4);
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            // 32 hex chars, globally unique (not restaurant-scoped) —
            // this IS the entire trust boundary for the public QR
            // ordering flow, so it must never be guessable or reused.
            $table->string('qr_token', 32)->unique();
            $table->timestamps();

            $table->unique(['restaurant_id', 'table_number']);
            $table->index(['restaurant_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};