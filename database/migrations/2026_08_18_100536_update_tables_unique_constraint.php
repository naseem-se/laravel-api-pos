<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropUnique('tables_restaurant_id_table_number_unique');

            $table->unique(
                ['restaurant_id', 'branch_id', 'table_number'],
                'tables_restaurant_branch_table_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropUnique(
                'tables_restaurant_branch_table_number_unique'
            );

            $table->unique(
                ['restaurant_id', 'table_number'],
                'tables_restaurant_id_table_number_unique'
            );
        });
    }
};