<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('settled_shift_id')
                ->nullable()
                ->after('cashier_shift_id')
                ->constrained('cashier_shifts')
                ->nullOnDelete();
        });

        DB::statement("
            ALTER TABLE cashier_shifts
            MODIFY COLUMN discrepancy_resolution
            ENUM('ledger_adjustment', 'cashier_covered', 'manual_resolved', 'catchup_sale')
            NULL
        ");

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->foreignId('catchup_sale_id')
                ->nullable()
                ->after('finance_transaction_id')
                ->constrained('sales')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catchup_sale_id');
        });

        DB::statement("
            ALTER TABLE cashier_shifts
            MODIFY COLUMN discrepancy_resolution
            ENUM('ledger_adjustment', 'cashier_covered', 'manual_resolved')
            NULL
        ");

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('settled_shift_id');
        });
    }
};
