<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->enum('sales_payment_method', [
                'cash',
                'qris',
                'transfer',
            ])
                ->nullable()
                ->unique()
                ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->dropUnique([
                'sales_payment_method',
            ]);

            $table->dropColumn('sales_payment_method');
        });
    }
};
