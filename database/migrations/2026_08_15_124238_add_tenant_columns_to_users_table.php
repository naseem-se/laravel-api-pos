<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable: superadmin has no restaurant. Cascade delete on
            // restaurant is deliberately NOT set here — deleting a
            // restaurant should never silently cascade-delete staff
            // accounts; that's a decision that belongs to an explicit
            // admin action, not a foreign key side effect.
            $table->foreignId('restaurant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('restaurant_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index(['restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['restaurant_id', 'branch_id', 'is_active', 'last_login_at']);
        });
    }
};