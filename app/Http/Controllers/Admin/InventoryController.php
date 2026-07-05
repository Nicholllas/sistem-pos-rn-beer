<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function stockIn()
    {
        $products = Product::with('category')->active()->orderBy('name')->get();

        return view('admin.inventory.stock-in', compact('products'));
    }

    public function storeStockIn(Request $request)
    {
        $request->merge(['note' => $this->normalizeNote($request->note)]);

        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'qty'        => ['required', 'integer', 'min:1'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'note'       => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::whereKey($validated['product_id'])
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $qty           = (int) $validated['qty'];
            $costPrice     = (float) $validated['cost_price'];
            $previousStock = $product->stock;
            $newStock      = $previousStock + $qty;

            $product->update([
                'stock'                 => $newStock,
                'cost_price'            => $costPrice,
                'last_stock_updated_by' => Auth::id(),
                'last_stock_updated_at' => now(),
                'updated_by'            => Auth::id(),
            ]);

            $stockBatch = StockBatch::create([
                'product_id'    => $product->id,
                'source_type'   => 'stock_in',
                'source_id'     => null,
                'qty_in'        => $qty,
                'qty_remaining' => $qty,
                'cost_price'    => $costPrice,
                'batch_date'    => now()->toDateString(),
                'created_by'    => Auth::id(),
            ]);

            StockMovement::create([
                'product_id'     => $product->id,
                'type'           => 'in',
                'qty'            => $qty,
                'previous_stock' => $previousStock,
                'new_stock'      => $newStock,
                'note'           => ($validated['note'] ?? 'Stok masuk manual')
                    . ' | Batch FIFO #' . $stockBatch->id
                    . ' | Modal: Rp ' . number_format($costPrice, 0, ',', '.'),
                'created_by'     => Auth::id(),
            ]);
        });

        return redirect()->route('admin.inventory.stock-in')->with('success', 'Stok masuk berhasil disimpan dan batch FIFO sudah dibuat.');
    }

    public function adjustment()
    {
        $products = Product::with('category')->active()->orderBy('name')->get();

        return view('admin.inventory.adjustment', compact('products'));
    }

    public function storeAdjustment(Request $request)
    {
        $request->merge(['note' => $this->normalizeNote($request->note)]);

        $validated = $request->validate([
            'product_id'   => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'actual_stock' => ['required', 'integer', 'min:0'],
            'note'         => ['required', 'string', 'max:1000'],
        ]);

        $hasChanged = false;

        DB::transaction(function () use ($validated, &$hasChanged) {
            $product = Product::whereKey($validated['product_id'])
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $previousStock = (int) $product->stock;
            $newStock      = (int) $validated['actual_stock'];
            $difference    = $newStock - $previousStock;

            if ($difference === 0) {
                return;
            }

            $hasChanged = true;

            if ($difference > 0) {
                $costPrice  = (float) ($product->cost_price ?? 0);
                $stockBatch = StockBatch::create([
                    'product_id'    => $product->id,
                    'source_type'   => 'adjustment',
                    'source_id'     => null,
                    'qty_in'        => $difference,
                    'qty_remaining' => $difference,
                    'cost_price'    => $costPrice,
                    'batch_date'    => now()->toDateString(),
                    'created_by'    => Auth::id(),
                ]);

                $movementNote = $validated['note']
                    . ' | Penyesuaian stok tambah'
                    . ' | Batch FIFO #' . $stockBatch->id
                    . ' | Modal: Rp ' . number_format($costPrice, 0, ',', '.');
            } else {
                $usedBatches  = $this->reduceFifoBatchesForAdjustment($product, abs($difference));
                $batchNotes   = collect($usedBatches)
                    ->map(fn ($b) => 'Batch FIFO #' . $b['stock_batch_id'] . ' qty ' . $b['qty'])
                    ->implode(', ');

                $movementNote = $validated['note'] . ' | Penyesuaian stok kurang | ' . $batchNotes;
            }

            $product->update([
                'stock'                 => $newStock,
                'last_stock_updated_by' => Auth::id(),
                'last_stock_updated_at' => now(),
                'updated_by'            => Auth::id(),
            ]);

            StockMovement::create([
                'product_id'     => $product->id,
                'type'           => 'adjustment',
                'qty'            => $difference,
                'previous_stock' => $previousStock,
                'new_stock'      => $newStock,
                'note'           => $movementNote,
                'created_by'     => Auth::id(),
            ]);
        });

        $message = $hasChanged
            ? 'Penyesuaian stok berhasil disimpan dan batch FIFO sudah disesuaikan.'
            : 'Stok tidak berubah karena stok fisik sama dengan stok sistem.';

        return redirect()->route('admin.inventory.adjustment')->with('success', $message);
    }

    public function lowStock()
    {
        $products = Product::with(['category', 'lastStockUpdatedBy'])
            ->active()
            ->lowStock()
            ->orderBy('stock')
            ->paginate(10);

        return view('admin.inventory.low-stock', compact('products'));
    }

    public function logs()
    {
        $stockMovements = StockMovement::with(['product.category', 'createdBy'])->latest()->paginate(15);

        return view('admin.inventory.logs', compact('stockMovements'));
    }

    public function batches(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => ['nullable', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'source_type' => ['nullable', Rule::in(['initial_stock', 'stock_in', 'purchase', 'adjustment', 'sale_void'])],
            'status'      => ['nullable', Rule::in(['active', 'empty'])],
        ]);

        $products = Product::with('category')->orderBy('name')->get();

        $sourceLabels = [
            'initial_stock' => 'Stok Awal',
            'stock_in'      => 'Stok Masuk Manual',
            'purchase'      => 'Pembelian Stok',
            'adjustment'    => 'Penyesuaian Stok',
            'sale_void'     => 'Void Penjualan',
        ];

        $query = StockBatch::with(['product.category', 'createdBy'])
            ->when(!empty($validated['product_id']), fn ($q) => $q->where('product_id', $validated['product_id']))
            ->when(!empty($validated['source_type']), fn ($q) => $q->where('source_type', $validated['source_type']))
            ->when(!empty($validated['status']), function ($q) use ($validated) {
                $validated['status'] === 'active'
                    ? $q->where('qty_remaining', '>', 0)
                    : $q->where('qty_remaining', '<=', 0);
            });

        $totalBatches        = (clone $query)->count();
        $activeBatches       = (clone $query)->where('qty_remaining', '>', 0)->count();
        $emptyBatches        = (clone $query)->where('qty_remaining', '<=', 0)->count();
        $totalRemainingQty   = (clone $query)->sum('qty_remaining');
        $totalRemainingValue = (clone $query)->selectRaw('COALESCE(SUM(qty_remaining * cost_price), 0) as total_value')->value('total_value');

        $batches = $query->orderByDesc('batch_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.inventory.batches', compact(
            'batches',
            'products',
            'sourceLabels',
            'totalBatches',
            'activeBatches',
            'emptyBatches',
            'totalRemainingQty',
            'totalRemainingValue',
        ));
    }

    private function reduceFifoBatchesForAdjustment(Product $product, int $qty): array
    {
        $remainingQty = $qty;
        $usedBatches  = [];

        $batches = StockBatch::where('product_id', $product->id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('batch_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            $takenQty = min($remainingQty, (int) $batch->qty_remaining);

            $batch->update(['qty_remaining' => (int) $batch->qty_remaining - $takenQty]);

            $usedBatches[] = [
                'stock_batch_id' => $batch->id,
                'qty'            => $takenQty,
                'cost_price'     => (float) $batch->cost_price,
                'subtotal_cost'  => $takenQty * (float) $batch->cost_price,
            ];

            $remainingQty -= $takenQty;
        }

        if ($remainingQty > 0) {
            throw ValidationException::withMessages([
                'actual_stock' => "Batch FIFO produk {$product->name} tidak cukup untuk penyesuaian stok. Silakan cek sinkronisasi stock_batches.",
            ]);
        }

        return $usedBatches;
    }

    private function normalizeNote(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        return $value === '' ? null : $value;
    }
}