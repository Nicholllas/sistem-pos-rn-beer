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
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->foreignId('closed_supervisor_id')
                ->nullable()
                ->after('closed_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('discrepancy_status', ['none', 'pending', 'resolved'])
                ->default('none')
                ->after('cash_discrepancy');

            $table->enum('discrepancy_resolution', ['ledger_adjustment', 'cashier_covered', 'manual_resolved'])
                ->nullable()
                ->after('discrepancy_status');

            $table->foreignId('finance_transaction_id')
                ->nullable()
                ->after('discrepancy_resolution')
                ->constrained('finance_transactions')
                ->nullOnDelete();

            $table->foreignId('resolved_by')
                ->nullable()
                ->after('finance_transaction_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('resolved_at')
                ->nullable()
                ->after('resolved_by');

            $table->text('resolution_notes')
                ->nullable()
                ->after('resolved_at');

            $table->index(['discrepancy_status', 'status']);
        });

        DB::table('cashier_shifts')
            ->where('status', 'closed')
            ->whereNotNull('cash_discrepancy')
            ->where('cash_discrepancy', '!=', 0)
            ->update(['discrepancy_status' => 'pending']);

        $now = now();
        $systemCategories = [
            [
                'code' => 'CASH_SHORTAGE',
                'name' => 'SELISIH KAS KURANG (SHIFT)',
                'type' => 'expense',
            ],
            [
                'code' => 'CASH_OVERAGE',
                'name' => 'SELISIH KAS LEBIH (SHIFT)',
                'type' => 'income',
            ],
        ];

        foreach ($systemCategories as $cat) {
            if (! DB::table('finance_categories')->where('code', $cat['code'])->exists()) {
                DB::table('finance_categories')->insert([
                    'code' => $cat['code'],
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropIndex(['discrepancy_status', 'status']);
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropConstrainedForeignId('finance_transaction_id');
            $table->dropConstrainedForeignId('closed_supervisor_id');
            $table->dropColumn([
                'discrepancy_status',
                'discrepancy_resolution',
                'resolved_at',
                'resolution_notes',
            ]);
        });
    }
};
