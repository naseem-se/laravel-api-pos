<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['restaurant_id', 'name']);
        });

        if (! Schema::hasTable('inventory_items')) Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('category', 100)->nullable();
            $table->string('unit', 30);
            $table->decimal('current_stock', 14, 4)->default(0);
            $table->decimal('low_stock_threshold', 14, 4)->default(0);
            $table->decimal('cost_per_unit', 12, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['restaurant_id', 'sku']);
            $table->index(['restaurant_id', 'name']);
        });

        if (! Schema::hasTable('inventory_transactions')) Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('quantity_change', 14, 4);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('balance_after', 14, 4);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['restaurant_id', 'inventory_item_id', 'created_at'], 'inventory_transactions_item_date_idx');
            $table->index(['restaurant_id', 'reference_type', 'reference_id'], 'inventory_transactions_reference_idx');
        });

        if (! Schema::hasTable('menu_item_inventory')) Schema::create('menu_item_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_per_item', 14, 4);
            $table->timestamps();
            $table->unique(['menu_item_id', 'inventory_item_id']);
            $table->index('restaurant_id');
        });

        if (! Schema::hasTable('purchases')) Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purchase_number')->nullable();
            $table->string('supplier_invoice_number')->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('purchase_date');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['restaurant_id', 'purchase_number']);
            $table->index(['restaurant_id', 'status', 'purchase_date']);
        });

        if (! Schema::hasTable('purchase_items')) Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->string('unit', 30);
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        if (! Schema::hasTable('purchase_payments')) Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 30)->default('cash');
            $table->string('reference', 150)->nullable();
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'paid_at']);
        });

        if (! Schema::hasTable('expenses')) Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 100);
            $table->string('description');
            $table->string('vendor')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference', 150)->nullable();
            $table->date('expense_date');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_void')->default(false);
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'expense_date']);
            $table->index(['restaurant_id', 'category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('menu_item_inventory');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
    }
};
