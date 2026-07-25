<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_number', 50)->unique();
            $table->dateTime('transaction_date');

            $table->enum('type', [
                'income',
                'expense',
                'transfer',
            ]);

            /*
             * Income:
             * account_id = akun penerima uang
             *
             * Expense:
             * account_id = akun sumber pengeluaran
             *
             * Transfer:
             * account_id = akun sumber
             * transfer_account_id = akun tujuan
             */
            $table->foreignId('account_id')
                ->constrained('finance_accounts')
                ->restrictOnDelete();

            $table->foreignId('transfer_account_id')
                ->nullable()
                ->constrained('finance_accounts')
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('finance_categories')
                ->restrictOnDelete();

            $table->decimal('amount', 18, 2);

            $table->string('reference_number', 100)->nullable();
            $table->nullableMorphs('reference');

            $table->text('description')->nullable();

            $table->enum('status', [
                'posted',
                'void',
            ])->default('posted');

            $table->dateTime('voided_at')->nullable();

            $table->foreignId('voided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_date', 'status']);
            $table->index(['account_id', 'type', 'status']);
            $table->index(['transfer_account_id', 'status']);
            $table->index(['category_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
