<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->unique(
                [
                    'reference_type',
                    'reference_id',
                    'account_id',
                    'type',
                ],
                'finance_transactions_reference_account_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropUnique(
                'finance_transactions_reference_account_unique'
            );
        });
    }
};
