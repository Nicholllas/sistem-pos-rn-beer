<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInitialStockBatches extends Command
{
    protected $signature = 'stock:backfill-initial-batches';

    protected $description = 'Create initial FIFO stock batches from current product stock';

    public function handle(): int
    {
        DB::transaction(function () {
            $products = Product::where('stock', '>', 0)->get();

            foreach ($products as $product) {
                $alreadyExists = StockBatch::where('product_id', $product->id)
                    ->where('source_type', 'initial_stock')
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                StockBatch::create([
                    'product_id' => $product->id,
                    'source_type' => 'initial_stock',
                    'source_id' => null,
                    'qty_in' => $product->stock,
                    'qty_remaining' => $product->stock,
                    'cost_price' => $product->cost_price ?? 0,
                    'batch_date' => now()->toDateString(),
                    'created_by' => null,
                ]);
            }
        });

        $this->info('Initial stock batches created successfully.');

        return self::SUCCESS;
    }
}
