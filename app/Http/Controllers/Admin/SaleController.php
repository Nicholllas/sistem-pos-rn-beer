<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
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

            'items.*.qty' => [
                'required',
                'integer',
                'min:1',
            ],

            'payments' => [
                'required',
                'array',
                'min:1',
            ],

            'payments.*.payment_method' => [
                'required',
                Rule::in(['cash', 'qris', 'transfer']),
            ],

            'payments.*.amount' => [
                'required',
                'numeric',
                'min:0',
            ],
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

            $total = 0;
            $preparedItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()
                    ->where('is_active', true)
                    ->findOrFail($item['product_id']);

                $qty = (int) $item['qty'];

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} tidak cukup. Stok tersedia: {$product->stock}.",
                    ]);
                }

                $subtotal = $product->price * $qty;
                $total += $subtotal;

                $preparedItems[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'price' => $product->price,
                    'cost_price' => $product->cost_price,
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

            $cashPaid = $payments
                ->where('payment_method', 'cash')
                ->sum('amount');

            $nonCashPaid = $payments
                ->whereIn('payment_method', ['qris', 'transfer'])
                ->sum('amount');

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

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $product->update([
                    'stock' => $item['new_stock'],
                    'last_stock_updated_by' => Auth::id(),
                    'last_stock_updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'qty' => -$item['qty'],
                    'previous_stock' => $item['previous_stock'],
                    'new_stock' => $item['new_stock'],
                    'note' => 'Penjualan invoice ' . $sale->invoice_number,
                    'created_by' => Auth::id(),
                ]);
            }

            return $sale;
        });

        return redirect()
            ->route('admin.sales.pos')
            ->with('success', 'Transaksi berhasil. Invoice: ' . $sale->invoice_number);
    }

    public function history()
    {
        $sales = Sale::with([
                'soldBy',
                'payments',
            ])
            ->latest()
            ->paginate(10);

        return view('admin.sales.history', compact('sales'));
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';

        $lastSale = Sale::where('invoice_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $next = $lastSale
            ? ((int) str_replace($prefix, '', $lastSale->invoice_number)) + 1
            : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}