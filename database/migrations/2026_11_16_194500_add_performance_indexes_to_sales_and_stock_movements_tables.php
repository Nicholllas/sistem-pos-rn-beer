<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'sales_status_created_at_index');
            $table->index(['payment_method', 'status', 'created_at'], 'sales_payment_status_created_at_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['created_at', 'type'], 'stock_movements_created_at_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_status_created_at_index');
            $table->dropIndex('sales_payment_status_created_at_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_created_at_type_index');
        });
    }
};
