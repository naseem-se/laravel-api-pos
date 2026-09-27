<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('purpose', ['receipt', 'kitchen', 'both'])->default('both');
            $table->enum('connection_type', ['network', 'system']);
            $table->string('ip')->nullable();
            $table->unsignedInteger('port')->default(9100);
            $table->string('system_printer_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};