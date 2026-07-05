<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            /**
             * Untuk fleksibel:
             * source_type bisa:
             * - initial_stock
             * - stock_in
             * - purchase
             * - adjustment
             * - sale_void
             */
            $table->string('source_type')->default('stock_in');
            $table->unsignedBigInteger('source_id')->nullable();

            $table->integer('qty_in');
            $table->integer('qty_remaining');

            $table->decimal('cost_price', 15, 2);

            $table->date('batch_date');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'qty_remaining']);
            $table->index(['product_id', 'batch_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};