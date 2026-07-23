<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\SalePayment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function pos()
    {
        $products = Product::with('category')
            ->active()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('admin.sales.pos', compact('products'));
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', Rule::in(['cash', 'qris', 'transfer'])],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Minimal harus ada 1 produk di keranjang.',
            'payments.required' => 'Minimal satu metode pembayaran harus diisi.',
            'payments.min' => 'Minimal satu metode pembayaran harus diisi.',
            'payments.*.payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payments.*.payment_method.in' => 'Metode pembayaran tidak valid.',
            'payments.*.amount.required' => 'Nominal pembayaran wajib diisi.',
            'payments.*.amount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'payments.*.amount.min' => 'Nominal pembayaran tidak boleh minus.',
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $invoiceNumber = $this->generateInvoiceNumber();

            $cartItems = collect($validated['items'])
                ->groupBy(fn ($item) => (int) $item['product_id'])
                ->map(function ($items, $productId) {
                    return [
                        'product_id' => (int) $productId,
                        'qty' => $items->sum(fn ($item) => (int) $item['qty']),
                    ];
                })
                ->sortBy('product_id')
                ->values();

            $total = 0;
            $preparedItems = [];

            foreach ($cartItems as $item) {
                $product = Product::whereKey($item['product_id'])
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->firstOrFail();

                $qty = (int) $item['qty'];

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} tidak cukup. Stok tersedia: {$product->stock}.",
                    ]);
                }

                $subtotal = (float) $product->price * $qty;
                $total += $subtotal;

                $preparedItems[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'price' => (float) $product->price,
                    'subtotal' => $subtotal,
                    'previous_stock' => $product->stock,
                    'new_stock' => $product->stock - $qty,
                ];
            }

            $payments = collect($validated['payments'])
                ->map(function ($payment) {
                    return [
                        'payment_method' => strtolower(trim($payment['payment_method'])),
                        'amount' => (float) $payment['amount'],
                    ];
                })
                ->filter(fn ($payment) => $payment['amount'] > 0)
                ->groupBy('payment_method')
                ->map(function ($items, $method) {
                    return [
                        'payment_method' => $method,
                        'amount' => $items->sum('amount'),
                    ];
                })
                ->values();

            if ($payments->isEmpty()) {
                throw ValidationException::withMessages([
                    'payments' => 'Minimal satu pembayaran harus diisi.',
                ]);
            }

            $totalPaid = $payments->sum('amount');
            $cashPaid = $payments->where('payment_method', 'cash')->sum('amount');
            $nonCashPaid = $payments->whereIn('payment_method', ['qris', 'transfer'])->sum('amount');

            if ($totalPaid < $total) {
                throw ValidationException::withMessages([
                    'payments' => 'Total pembayaran tidak boleh kurang dari total transaksi.',
                ]);
            }

            if ($nonCashPaid > $total) {
                throw ValidationException::withMessages([
                    'payments' => 'Total pembayaran QRIS/transfer tidak boleh melebihi total transaksi.',
                ]);
            }

            $changeAmount = $totalPaid - $total;

            if ($changeAmount > 0 && $cashPaid <= 0) {
                throw ValidationException::withMessages([
                    'payments' => 'Kelebihan pembayaran hanya diperbolehkan jika ada pembayaran cash.',
                ]);
            }

            $paymentMethod = $payments->count() > 1
                ? 'split'
                : $payments->first()['payment_method'];

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'sold_by' => Auth::id(),
                'total' => $total,
                'paid_amount' => $totalPaid,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
            ]);

            foreach ($payments as $payment) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                ]);
            }

            foreach ($preparedItems as $item) {
                $product = $item['product'];
                $qty = $item['qty'];

                $fifoCost = $this->consumeFifoBatches($product, $qty);

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $item['price'],
                    'cost_price' => $fifoCost['average_cost'],
                    'subtotal' => $item['subtotal'],
                ]);

                foreach ($fifoCost['batches'] as $batch) {
                    SaleItemBatch::create([
                        'sale_item_id' => $saleItem->id,
                        'stock_batch_id' => $batch['stock_batch_id'],
                        'qty' => $batch['qty'],
                        'cost_price' => $batch['cost_price'],
                        'subtotal_cost' => $batch['subtotal_cost'],
                    ]);
                }

                $product->update([
                    'stock' => $item['new_stock'],
                    'last_stock_updated_by' => Auth::id(),
                    'last_stock_updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'qty' => -$qty,
                    'previous_stock' => $item['previous_stock'],
                    'new_stock' => $item['new_stock'],
                    'note' => 'Penjualan invoice '.$sale->invoice_number,
                    'created_by' => Auth::id(),
                ]);
            }

            return $sale;
        });

        return redirect()
            ->route('admin.sales.receipt', $sale)
            ->with('success', 'Transaksi berhasil. Invoice: '.$sale->invoice_number);
    }

    public function history(Request $request)
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'cashier_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'status' => [
                'nullable',
                'in:completed,void',
            ],

            'payment_method' => [
                'nullable',
                'in:cash,qris,transfer,split',
            ],
        ]);

        $sales = Sale::query()
            ->with([
                'soldBy:id,name',
            ])

            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(
                        'invoice_number',
                        'like',
                        '%'.trim($search).'%'
                    );
                }
            )

            ->when(
                $filters['start_date'] ?? null,
                function ($query, $startDate) {
                    $query->whereDate('created_at', '>=', $startDate);
                }
            )

            ->when(
                $filters['end_date'] ?? null,
                function ($query, $endDate) {
                    $query->whereDate('created_at', '<=', $endDate);
                }
            )

            ->when(
                $filters['cashier_id'] ?? null,
                function ($query, $cashierId) {
                    $query->where('sold_by', $cashierId);
                }
            )

            ->when(
                $filters['status'] ?? null,
                function ($query, $status) {
                    $query->where('status', $status);
                }
            )

            ->when(
                $filters['payment_method'] ?? null,
                function ($query, $paymentMethod) {
                    $query->where('payment_method', $paymentMethod);
                }
            )

            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $cashiers = User::query()
            ->whereIn(
                'id',
                Sale::query()
                    ->select('sold_by')
                    ->whereNotNull('sold_by')
                    ->distinct()
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'is_active',
            ]);

        return view('admin.sales.history', compact(
            'sales',
            'cashiers'
        ));
    }

    public function receipt(Sale $sale)
    {
        $sale->load([
            'soldBy',
            'voidedBy',
            'payments',
            'items.product',
            'items.saleItemBatches.stockBatch',
        ]);

        return view('admin.sales.receipt', compact('sale'));
    }

    public function void(Request $request, Sale $sale)
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
            'void_reason.required' => 'Alasan void wajib diisi.',
        ]);

        DB::transaction(function () use ($sale, $validated) {
            $sale = Sale::whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status === 'void') {
                throw ValidationException::withMessages([
                    'void_reason' => 'Transaksi ini sudah pernah dibatalkan.',
                ]);
            }

            $sale->load([
                'items.product',
                'items.saleItemBatches',
            ]);

            foreach ($sale->items as $item) {
                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $previousStock = $product->stock;
                $newStock = $previousStock + $item->qty;

                if ($item->saleItemBatches->isNotEmpty()) {
                    foreach ($item->saleItemBatches as $saleItemBatch) {
                        $stockBatch = StockBatch::whereKey($saleItemBatch->stock_batch_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $restoredQty = $stockBatch->qty_remaining + $saleItemBatch->qty;

                        if ($restoredQty > $stockBatch->qty_in) {
                            throw ValidationException::withMessages([
                                'void_reason' => "Batch FIFO #{$stockBatch->id} melebihi qty awal saat restore.",
                            ]);
                        }

                        $stockBatch->update([
                            'qty_remaining' => $restoredQty,
                        ]);
                    }
                } else {
                    StockBatch::create([
                        'product_id' => $product->id,
                        'source_type' => 'sale_void',
                        'source_id' => $item->id,
                        'qty_in' => $item->qty,
                        'qty_remaining' => $item->qty,
                        'cost_price' => $item->cost_price,
                        'batch_date' => now()->toDateString(),
                        'created_by' => Auth::id(),
                    ]);
                }

                $product->update([
                    'stock' => $newStock,
                    'last_stock_updated_by' => Auth::id(),
                    'last_stock_updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'qty' => $item->qty,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'note' => 'Void transaksi invoice '.$sale->invoice_number.'. Alasan: '.$validated['void_reason'],
                    'created_by' => Auth::id(),
                ]);
            }

            $sale->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'void_reason' => $validated['void_reason'],
            ]);
        });

        return redirect()
            ->route('admin.sales.receipt', $sale)
            ->with('success', 'Transaksi berhasil dibatalkan dan stok sudah dikembalikan.');
    }

    private function consumeFifoBatches(Product $product, int $qty): array
    {
        $remainingQty = $qty;
        $totalCost = 0;
        $usedBatches = [];

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

            $takenQty = min($remainingQty, $batch->qty_remaining);
            $costPrice = (float) $batch->cost_price;
            $subtotalCost = $takenQty * $costPrice;

            $batch->update([
                'qty_remaining' => $batch->qty_remaining - $takenQty,
            ]);

            $usedBatches[] = [
                'stock_batch_id' => $batch->id,
                'qty' => $takenQty,
                'cost_price' => $costPrice,
                'subtotal_cost' => $subtotalCost,
            ];

            $totalCost += $subtotalCost;
            $remainingQty -= $takenQty;
        }

        if ($remainingQty > 0) {
            throw ValidationException::withMessages([
                'items' => "Batch FIFO produk {$product->name} tidak cukup. Silakan cek stock_batches.",
            ]);
        }

        return [
            'total_cost' => $totalCost,
            'average_cost' => $qty > 0 ? $totalCost / $qty : 0,
            'batches' => $usedBatches,
        ];
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

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';

        $lastSale = Sale::where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = $lastSale
            ? ((int) str_replace($prefix, '', $lastSale->invoice_number)) + 1
            : 1;

        return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
