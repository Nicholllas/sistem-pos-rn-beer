<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Finance\FinancePostingService;
use App\Support\TextNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly FinancePostingService $financePostingService
    ) {}

    public function index()
    {
        $purchases = Purchase::query()
            ->with([
                'supplier',
                'financeAccount',
                'createdBy',
            ])
            ->latest()
            ->paginate(10);

        return view(
            'admin.purchases.index',
            compact('purchases')
        );
    }

    public function create()
    {
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $financeAccounts = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'admin.purchases.create',
            compact(
                'suppliers',
                'products',
                'financeAccounts'
            )
        );
    }

    public function store(Request $request)
    {
        $request->merge([
            'note' => TextNormalizer::multiline(
                $request->input('note')
            ),
        ]);

        $validated = $request->validate([
            'supplier_id' => [
                'required',

                Rule::exists('suppliers', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],

            'finance_account_id' => [
                'required',

                Rule::exists('finance_accounts', 'id')
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
                "regex:/^[\pL\pN\s.,:;!?()'\"#%&_\/+\-=]+$/u",
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

            'finance_account_id.required' => 'Akun pembayaran wajib dipilih.',

            'finance_account_id.exists' => 'Akun pembayaran tidak valid atau tidak aktif.',

            'purchase_date.required' => 'Tanggal pembelian wajib diisi.',

            'note.regex' => 'Catatan pembelian mengandung karakter yang tidak diizinkan.',

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

            /*
             * Gabungkan produk duplikat agar satu produk tidak diproses
             * lebih dari sekali dalam pembelian yang sama.
             */
            $purchaseItems = collect($validated['items'])
                ->groupBy(
                    fn ($item) => (int) $item['product_id']
                )
                ->map(function ($items, $productId) {
                    $costPrices = $items
                        ->pluck('cost_price')
                        ->map(fn ($value) => (float) $value)
                        ->unique();

                    if ($costPrices->count() > 1) {
                        throw ValidationException::withMessages([
                            'items' => 'Produk yang sama tidak boleh memiliki harga modal berbeda dalam satu pembelian.',
                        ]);
                    }

                    return [
                        'product_id' => (int) $productId,
                        'qty' => $items->sum(
                            fn ($item) => (int) $item['qty']
                        ),
                        'cost_price' => (float) $items
                            ->first()['cost_price'],
                    ];
                })
                ->sortBy('product_id')
                ->values();

            $total = $purchaseItems->sum(
                fn ($item) => (int) $item['qty']
                    * (float) $item['cost_price']
            );

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,

                'supplier_id' => $validated['supplier_id'],

                'finance_account_id' => $validated['finance_account_id'],

                'purchase_date' => $validated['purchase_date'],

                'total' => $total,

                'status' => 'completed',

                'note' => $validated['note'] ?? null,

                'created_by' => Auth::id(),

                'updated_by' => Auth::id(),
            ]);

            foreach ($purchaseItems as $item) {
                $product = Product::query()
                    ->whereKey($item['product_id'])
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

                    'note' => 'PEMBELIAN STOK '
                        .$purchase->purchase_number
                        .' | BATCH FIFO #'
                        .$stockBatch->id
                        .' | MODAL: RP '
                        .number_format(
                            $costPrice,
                            0,
                            ',',
                            '.'
                        ),

                    'created_by' => Auth::id(),
                ]);
            }

            /*
             * Tetap di dalam DB transaction.
             *
             * Jika saldo akun tidak cukup atau kategori Finance belum
             * tersedia, purchase, batch FIFO, stok, dan stock movement
             * semuanya otomatis rollback.
             */
            $this->financePostingService->postPurchase(
                $purchase
            );
        });

        return redirect()
            ->route('admin.purchases.index')
            ->with(
                'success',
                'Pembelian stok berhasil disimpan, batch FIFO sudah dibuat, dan saldo Finance sudah diperbarui.'
            );
    }

    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'financeAccount',
            'items.product.category',
            'createdBy',
            'updatedBy',
            'voidedBy',
        ]);

        return view(
            'admin.purchases.show',
            compact('purchase')
        );
    }

    public function void(
        Request $request,
        Purchase $purchase
    ) {
        $request->merge([
            'void_reason' => TextNormalizer::multiline(
                $request->input('void_reason')
            ),
        ]);

        $validated = $request->validate([
            'void_reason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
                "regex:/^[\pL\pN\s.,:;!?()'\"#%&_\/+\-=]+$/u",
            ],
        ], [
            'void_reason.required' => 'Alasan pembatalan pembelian wajib diisi.',

            'void_reason.regex' => 'Alasan pembatalan mengandung karakter yang tidak diizinkan.',
        ]);

        DB::transaction(function () use (
            $purchase,
            $validated
        ) {
            $purchase = Purchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchase->status === 'void') {
                throw ValidationException::withMessages([
                    'void_reason' => 'Pembelian ini sudah pernah dibatalkan.',
                ]);
            }

            $purchase->load('items');

            foreach ($purchase->items as $item) {
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $batch = StockBatch::query()
                    ->where('source_type', 'purchase')
                    ->where('source_id', $item->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $usedQty =
                    $batch->qty_in
                    - $batch->qty_remaining;

                if ($usedQty > 0) {
                    throw ValidationException::withMessages([
                        'void_reason' => "Pembelian tidak dapat dibatalkan karena stok {$product->name} dari batch ini sudah terjual.",
                    ]);
                }

                if ($product->stock < $item->qty) {
                    throw ValidationException::withMessages([
                        'void_reason' => "Stok produk {$product->name} tidak cukup untuk membatalkan pembelian.",
                    ]);
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

                    'note' => 'VOID PEMBELIAN '
                        .$purchase->purchase_number
                        .' | BATCH FIFO #'
                        .$batch->id
                        .' | ALASAN: '
                        .$validated['void_reason'],

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

            $this->financePostingService->voidPurchase(
                $purchase,
                $validated['void_reason']
            );
        });

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with(
                'success',
                'Pembelian berhasil dibatalkan, stok dikurangi kembali, dan saldo Finance telah dipulihkan.'
            );
    }

    private function generatePurchaseNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';

        $lastPurchase = Purchase::query()
            ->where(
                'purchase_number',
                'like',
                $prefix.'%'
            )
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($lastPurchase) {
            $lastSequence = (int) str_replace(
                $prefix,
                '',
                $lastPurchase->purchase_number
            );

            $nextNumber = $lastSequence + 1;
        }

        return $prefix.str_pad(
            $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
