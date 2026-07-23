<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_item_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->cascadeOnDelete();

            $table->foreignId('stock_batch_id')
                ->constrained('stock_batches')
                ->restrictOnDelete();

            $table->integer('qty');

            $table->decimal('cost_price', 15, 2);
            $table->decimal('subtotal_cost', 15, 2);

            $table->timestamps();

            $table->index('sale_item_id');
            $table->index('stock_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_batches');
    }
};
