<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'createdBy'])
            ->latest()
            ->paginate(10);

        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'note' => $this->normalizeText($request->note),
        ]);

        $validated = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'purchase_date' => [
                'required',
                'date',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'items.*.qty' => [
                'required',
                'integer',
                'min:1',
            ],
            'items.*.cost_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ], [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'supplier_id.exists' => 'Supplier tidak valid atau tidak aktif.',
            'purchase_date.required' => 'Tanggal pembelian wajib diisi.',
            'items.required' => 'Minimal harus ada 1 produk pembelian.',
            'items.min' => 'Minimal harus ada 1 produk pembelian.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.exists' => 'Produk tidak valid atau tidak aktif.',
            'items.*.qty.required' => 'Qty wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
            'items.*.cost_price.required' => 'Harga modal wajib diisi.',
            'items.*.cost_price.min' => 'Harga modal tidak boleh minus.',
        ]);

        DB::transaction(function () use ($validated) {
            $purchaseNumber = $this->generatePurchaseNumber();

            $total = collect($validated['items'])->sum(function ($item) {
                return (int) $item['qty'] * (float) $item['cost_price'];
            });

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'total' => $total,
                'status' => 'completed',
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::whereKey($item['product_id'])
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->firstOrFail();

                $qty = (int) $item['qty'];
                $costPrice = (float) $item['cost_price'];
                $subtotal = $qty * $costPrice;

                $previousStock = $product->stock;
                $newStock = $previousStock + $qty;

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'cost_price' => $costPrice,
                    'subtotal' => $subtotal,
                ]);

                $stockBatch = StockBatch::create([
                    'product_id' => $product->id,
                    'source_type' => 'purchase',
                    'source_id' => $purchaseItem->id,
                    'qty_in' => $qty,
                    'qty_remaining' => $qty,
                    'cost_price' => $costPrice,
                    'batch_date' => $validated['purchase_date'],
                    'created_by' => Auth::id(),
                ]);

                $product->update([
                    'stock' => $newStock,
                    'cost_price' => $costPrice,
                    'last_stock_updated_by' => Auth::id(),
                    'last_stock_updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'qty' => $qty,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'note' => 'Pembelian stok '.$purchase->purchase_number
                        .' | Batch FIFO #'.$stockBatch->id
                        .' | Modal: Rp '.number_format($costPrice, 0, ',', '.'),
                    'created_by' => Auth::id(),
                ]);
            }
        });

        return redirect()
            ->route('admin.purchases.index')
            ->with('success', 'Pembelian stok berhasil disimpan dan batch FIFO sudah dibuat.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product.category',
            'createdBy',
            'updatedBy',
            'voidedBy',
        ]);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function void(Request $request, Purchase $purchase)
    {
        $request->merge([
            'void_reason' => $this->normalizeText($request->void_reason),
        ]);

        $validated = $request->validate([
            'void_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ], [
            'void_reason.required' => 'Alasan pembatalan pembelian wajib diisi.',
        ]);

        DB::transaction(function () use ($purchase, $validated) {
            $purchase = Purchase::whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchase->status === 'void') {
                abort(422, 'Pembelian ini sudah pernah dibatalkan.');
            }

            $purchase->load('items');

            foreach ($purchase->items as $item) {
                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $batch = StockBatch::where('source_type', 'purchase')
                    ->where('source_id', $item->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $usedQty = $batch->qty_in - $batch->qty_remaining;

                if ($usedQty > 0) {
                    abort(422, "Pembelian tidak bisa dibatalkan karena stok {$product->name} dari batch ini sudah terjual.");
                }

                if ($product->stock < $item->qty) {
                    abort(422, "Stok produk {$product->name} tidak cukup untuk membatalkan pembelian.");
                }

                $previousStock = $product->stock;
                $newStock = $previousStock - $item->qty;

                $batch->update([
                    'qty_remaining' => 0,
                ]);

                $product->update([
                    'stock' => $newStock,
                    'last_stock_updated_by' => Auth::id(),
                    'last_stock_updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'qty' => -$item->qty,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'note' => 'Void pembelian '.$purchase->purchase_number
                        .' | Batch FIFO #'.$batch->id
                        .' | Alasan: '.$validated['void_reason'],
                    'created_by' => Auth::id(),
                ]);
            }

            $purchase->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'void_reason' => $validated['void_reason'],
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Pembelian berhasil dibatalkan.');
    }

    private function generatePurchaseNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';

        $lastPurchase = Purchase::where('purchase_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($lastPurchase) {
            $lastSequence = (int) str_replace($prefix, '', $lastPurchase->purchase_number);
            $nextNumber = $lastSequence + 1;
        }

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strip_tags($value);
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? null : $value;
    }
}
