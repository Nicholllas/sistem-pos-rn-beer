<?php

namespace App\Services\Sales;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\SalePayment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Services\Finance\FinancePostingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SaleCheckoutService
{
    public function __construct(
        private readonly FinancePostingService $financePostingService
    ) {}

    /**
     * Record a completed sale, deduct FIFO stock batches, log stock movements, and post to Finance.
     * Must be invoked inside an active DB transaction.
     *
     * @param  array<int, array{product_id: int|string, qty: int|string}>  $rawItems
     * @param  array<int, array{payment_method: string, amount: float|int|string}>  $rawPayments
     */
    public function recordSale(
        array $rawItems,
        array $rawPayments,
        ?CashierShift $activeShift = null,
        ?CashierShift $settledShift = null,
        ?string $movementNoteSuffix = null
    ): Sale {
        $invoiceNumber = $this->generateInvoiceNumber();

        $cartItems = collect($rawItems)
            ->groupBy(fn ($item) => (int) $item['product_id'])
            ->map(function ($items, $productId) {
                return [
                    'product_id' => (int) $productId,
                    'qty' => $items->sum(fn ($item) => (int) $item['qty']),
                ];
            })
            ->sortBy('product_id')
            ->values();

        $total = 0.0;
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

        if ($settledShift !== null) {
            $overage = (float) $settledShift->cash_discrepancy;

            if (abs($total - $overage) > 0.01) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Total penjualan susulan (Rp %s) harus sama dengan nilai selisih lebih Shift #%d (Rp %s). Jika sebagian selisih bukan dari penjualan, gunakan Sesuaikan ke Cash Ledger.',
                        number_format($total, 0, ',', '.'),
                        $settledShift->id,
                        number_format($overage, 0, ',', '.')
                    ),
                ]);
            }

            $rawPayments = [
                [
                    'payment_method' => 'cash',
                    'amount' => $total,
                ],
            ];
        }

        $payments = collect($rawPayments)
            ->map(function ($payment) {
                return [
                    'payment_method' => strtolower(trim((string) $payment['payment_method'])),
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

        if ($nonCashPaid >= $total && $cashPaid > 0) {
            throw ValidationException::withMessages([
                'payments' => 'Pembayaran QRIS/transfer sudah melunasi total transaksi. Kosongkan nominal cash jika tidak ada sisa tagihan tunai.',
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
            'cashier_shift_id' => $activeShift?->id,
            'settled_shift_id' => $settledShift?->id,
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
                'note' => 'Penjualan invoice '.$sale->invoice_number.($movementNoteSuffix ? ' '.$movementNoteSuffix : ''),
                'created_by' => Auth::id(),
            ]);
        }

        $this->financePostingService->postSale($sale);

        return $sale;
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
