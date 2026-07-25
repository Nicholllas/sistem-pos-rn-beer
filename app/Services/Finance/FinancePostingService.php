<?php

namespace App\Services\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancePostingService
{
    public function postSale(Sale $sale): void
    {
        if ($sale->status !== 'completed') {
            return;
        }

        $sale->loadMissing('payments');

        $category = FinanceCategory::query()
            ->where('code', 'SALES')
            ->where('type', 'income')
            ->where('is_system', true)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'payments' => 'Kategori sistem Finance SALES belum tersedia atau tidak aktif.',
            ]);
        }

        $payments = $sale->payments
            ->groupBy('payment_method')
            ->map(function ($items, $method) {
                return [
                    'payment_method' => $method,
                    'amount' => (float) $items->sum('amount'),
                ];
            })
            ->values();

        foreach ($payments as $payment) {
            $method = $payment['payment_method'];
            $amount = (float) $payment['amount'];

            /*
             * Nilai pembayaran cash pada sale_payments adalah uang
             * yang diberikan pelanggan.
             *
             * Kembalian harus dikurangi karena tidak menambah kas toko.
             */
            if ($method === 'cash') {
                $amount -= (float) $sale->change_amount;
            }

            /*
             * Contoh:
             * total Rp100.000
             * QRIS Rp100.000
             * cash Rp50.000
             * kembalian Rp50.000
             *
             * Dampak kas cash = 0, sehingga tidak perlu dibuat transaksi.
             */
            if ($amount <= 0) {
                continue;
            }

            $account = FinanceAccount::query()
                ->where('sales_payment_method', $method)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'payments' => "Akun Finance untuk pembayaran {$method} belum dipetakan atau tidak aktif.",
                ]);
            }

            $alreadyPosted = FinanceTransaction::withTrashed()
                ->where(
                    'reference_type',
                    $sale->getMorphClass()
                )
                ->where('reference_id', $sale->id)
                ->where('account_id', $account->id)
                ->where('type', 'income')
                ->exists();

            if ($alreadyPosted) {
                continue;
            }

            FinanceTransaction::create([
                'transaction_number' => $this->generateTransactionNumber(),

                'transaction_date' => $sale->created_at ?? now(),

                'type' => 'income',

                'account_id' => $account->id,

                'transfer_account_id' => null,

                'category_id' => $category->id,

                'amount' => $amount,

                'reference_number' => $sale->invoice_number,

                'reference_type' => $sale->getMorphClass(),

                'reference_id' => $sale->id,

                'description' => sprintf(
                    'PENJUALAN %s - METODE %s',
                    $sale->invoice_number,
                    Str::upper($method)
                ),

                'status' => 'posted',

                'created_by' => $sale->sold_by ?? Auth::id(),

                'updated_by' => $sale->sold_by ?? Auth::id(),
            ]);
        }
    }

    public function voidSale(
        Sale $sale,
        string $reason
    ): void {
        $transactions = FinanceTransaction::query()
            ->where(
                'reference_type',
                $sale->getMorphClass()
            )
            ->where('reference_id', $sale->id)
            ->where('type', 'income')
            ->where('status', 'posted')
            ->lockForUpdate()
            ->get();

        /*
         * Penjualan lama yang dibuat sebelum modul Finance
         * tidak memiliki transaksi Finance.
         *
         * Void penjualan lama tetap boleh berjalan.
         */
        if ($transactions->isEmpty()) {
            return;
        }

        foreach ($transactions as $transaction) {
            $transaction->update([
                'status' => 'void',

                'void_reason' => sprintf(
                    'VOID PENJUALAN %s. ALASAN: %s',
                    $sale->invoice_number,
                    $reason
                ),

                'voided_at' => now(),

                'voided_by' => Auth::id(),

                'updated_by' => Auth::id(),
            ]);
        }
    }

    public function postPurchase(Purchase $purchase): void
    {
        if ($purchase->status !== 'completed') {
            return;
        }

        if (! $purchase->finance_account_id) {
            throw ValidationException::withMessages([
                'finance_account_id' => 'Akun pembayaran Finance belum dipilih.',
            ]);
        }

        $category = FinanceCategory::query()
            ->where('code', 'PURCHASE')
            ->where('type', 'expense')
            ->where('is_system', true)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'finance_account_id' => 'Kategori sistem Finance PURCHASE belum tersedia atau tidak aktif.',
            ]);
        }

        /*
         * Lock akun agar tidak digunakan bersamaan oleh proses pembelian
         * lain ketika saldo sedang diperiksa.
         */
        $account = FinanceAccount::query()
            ->whereKey($purchase->finance_account_id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'finance_account_id' => 'Akun pembayaran tidak ditemukan atau sudah tidak aktif.',
            ]);
        }

        $alreadyPosted = FinanceTransaction::withTrashed()
            ->where(
                'reference_type',
                $purchase->getMorphClass()
            )
            ->where('reference_id', $purchase->id)
            ->where('account_id', $account->id)
            ->where('type', 'expense')
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        $purchaseTotal = (float) $purchase->total;
        $currentBalance = (float) $account->current_balance;

        if ($currentBalance < $purchaseTotal) {
            throw ValidationException::withMessages([
                'finance_account_id' => sprintf(
                    'Saldo akun %s tidak cukup. Saldo tersedia Rp%s, sedangkan total pembelian Rp%s.',
                    $account->name,
                    number_format($currentBalance, 0, ',', '.'),
                    number_format($purchaseTotal, 0, ',', '.')
                ),
            ]);
        }

        FinanceTransaction::create([
            'transaction_number' => $this->generateTransactionNumber(),

            'transaction_date' => $purchase->purchase_date
                    ? $purchase->purchase_date->copy()->startOfDay()
                    : now(),

            'type' => 'expense',

            'account_id' => $account->id,

            'transfer_account_id' => null,

            'category_id' => $category->id,

            'amount' => $purchaseTotal,

            'reference_number' => $purchase->purchase_number,

            'reference_type' => $purchase->getMorphClass(),

            'reference_id' => $purchase->id,

            'description' => sprintf(
                'PEMBELIAN STOK %s',
                $purchase->purchase_number
            ),

            'status' => 'posted',

            'created_by' => $purchase->created_by ?? Auth::id(),

            'updated_by' => $purchase->created_by ?? Auth::id(),
        ]);
    }

    public function voidPurchase(
        Purchase $purchase,
        string $reason
    ): void {
        $transactions = FinanceTransaction::query()
            ->where(
                'reference_type',
                $purchase->getMorphClass()
            )
            ->where('reference_id', $purchase->id)
            ->where('type', 'expense')
            ->where('status', 'posted')
            ->lockForUpdate()
            ->get();

        /*
         * Pembelian lama sebelum integrasi Finance tetap bisa di-void.
         */
        if ($transactions->isEmpty()) {
            return;
        }

        foreach ($transactions as $transaction) {
            $transaction->update([
                'status' => 'void',

                'void_reason' => sprintf(
                    'VOID PEMBELIAN %s. ALASAN: %s',
                    $purchase->purchase_number,
                    $reason
                ),

                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }
    }

    private function generateTransactionNumber(): string
    {
        do {
            $number = sprintf(
                'FIN-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );
        } while (
            FinanceTransaction::withTrashed()
                ->where('transaction_number', $number)
                ->exists()
        );

        return $number;
    }
}
