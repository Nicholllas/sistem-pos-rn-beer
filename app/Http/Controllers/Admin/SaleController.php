<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Finance\FinancePostingService;
use App\Services\Sales\SaleCheckoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(
        private readonly FinancePostingService $financePostingService,
        private readonly SaleCheckoutService $saleCheckoutService
    ) {}

    public function pos()
    {
        $activeShift = CashierShift::open()
            ->where('user_id', Auth::id())
            ->latest('opened_at')
            ->first();

        $products = Product::with('category')
            ->active()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('admin.sales.pos', compact('products', 'activeShift'));
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
            $activeShift = CashierShift::open()
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->latest('opened_at')
                ->first();

            if (! $activeShift) {
                throw ValidationException::withMessages([
                    'items' => 'Shift kasir belum dibuka. Silakan buka shift kasir terlebih dahulu dengan mengisi modal awal.',
                ]);
            }

            $sale = $this->saleCheckoutService->recordSale(
                $validated['items'],
                $validated['payments'],
                activeShift: $activeShift
            );

            $activeShift->recalculateTotals();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'Sales',
                'action' => 'checkout',
                'description' => "Checkout transaksi {$sale->invoice_number} senilai Rp ".number_format((float) $sale->total, 0, ',', '.')." ({$sale->payment_method})",
                'ip_address' => request()->ip(),
            ]);

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
                'settledShift:id',
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
                    $query->where(
                        'created_at',
                        '>=',
                        Carbon::parse($startDate)->startOfDay()
                    );
                }
            )

            ->when(
                $filters['end_date'] ?? null,
                function ($query, $endDate) {
                    $query->where(
                        'created_at',
                        '<=',
                        Carbon::parse($endDate)->endOfDay()
                    );
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
            'settledShift',
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

        $rules = [
            'void_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];

        $authorizedSupervisor = null;
        if (! Auth::user()->canAuthorizeCashier()) {
            $rules['supervisor_pin'] = ['required', 'string'];
        }

        $validated = $request->validate($rules, [
            'void_reason.required' => 'Alasan void wajib diisi.',
            'supervisor_pin.required' => 'PIN Supervisor wajib diisi untuk otorisasi pembatalan transaksi.',
        ]);

        if (! Auth::user()->canAuthorizeCashier()) {
            $authorizedSupervisor = User::verifySupervisorPin($validated['supervisor_pin']);

            if (! $authorizedSupervisor) {
                throw ValidationException::withMessages([
                    'supervisor_pin' => 'PIN Supervisor tidak valid atau tidak memiliki hak akses otorisasi.',
                ]);
            }
        }

        DB::transaction(function () use ($sale, $validated, $authorizedSupervisor, $request) {
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

            $this->financePostingService->voidSale(
                $sale,
                $validated['void_reason']
            );

            $shiftNote = '';
            if ($sale->cashierShift) {
                if ($sale->cashierShift->status === 'open') {
                    $sale->cashierShift->recalculateTotals();
                } else {
                    $shiftNote = " [Transaksi dari Shift #{$sale->cashierShift->id} yang sudah ditutup]";
                }
            }

            if ($sale->settled_shift_id) {
                $settledShift = CashierShift::whereKey($sale->settled_shift_id)
                    ->lockForUpdate()
                    ->first();

                if (
                    $settledShift
                    && $settledShift->discrepancy_resolution === 'catchup_sale'
                    && $settledShift->catchup_sale_id === $sale->id
                ) {
                    $settledShift->update([
                        'discrepancy_status' => 'pending',
                        'discrepancy_resolution' => null,
                        'catchup_sale_id' => null,
                        'finance_transaction_id' => null,
                        'resolved_by' => null,
                        'resolved_at' => null,
                        'resolution_notes' => null,
                    ]);

                    $shiftNote .= " [Selisih lebih Shift #{$settledShift->id} dibuka kembali ke Pending]";
                }
            }

            $authNote = $authorizedSupervisor ? ' (Diotorisasi oleh Supervisor: '.$authorizedSupervisor->name.')' : '';

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'Sales',
                'action' => 'void',
                'description' => "Void transaksi {$sale->invoice_number}. Alasan: {$validated['void_reason']}{$authNote}{$shiftNote}",
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('admin.sales.receipt', $sale)
            ->with('success', 'Transaksi berhasil dibatalkan dan stok sudah dikembalikan.');
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
