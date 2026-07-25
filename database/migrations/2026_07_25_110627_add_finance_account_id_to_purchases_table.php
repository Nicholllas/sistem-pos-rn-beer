<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            /*
             * Nullable agar data pembelian lama tetap aman.
             * Pembelian baru akan diwajibkan memilih akun melalui controller.
             */
            $table->foreignId('finance_account_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained('finance_accounts')
                ->restrictOnDelete();

            $table->index([
                'finance_account_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex([
                'finance_account_id',
                'status',
            ]);

            $table->dropConstrainedForeignId(
                'finance_account_id'
            );
        });
    }
};
