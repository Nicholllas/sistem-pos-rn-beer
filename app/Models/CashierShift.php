<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'starting_cash',
        'total_cash_sales',
        'total_qris_sales',
        'total_transfer_sales',
        'total_sales',
        'total_cash_refund',
        'expected_cash',
        'actual_cash',
        'cash_discrepancy',
        'discrepancy_status',
        'discrepancy_resolution',
        'finance_transaction_id',
        'catchup_sale_id',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'status',
        'closing_notes',
        'closed_by',
        'closed_supervisor_id',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'starting_cash' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_qris_sales' => 'decimal:2',
        'total_transfer_sales' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash_refund' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_discrepancy' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function closedSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_supervisor_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function financeTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function catchupSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'catchup_sale_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_shift_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function hasDiscrepancy(): bool
    {
        return $this->cash_discrepancy !== null && (float) $this->cash_discrepancy != 0.0;
    }

    public function isPendingDiscrepancy(): bool
    {
        return $this->isClosed() && $this->discrepancy_status === 'pending' && $this->hasDiscrepancy();
    }

    public function discrepancyResolutionLabel(): string
    {
        return match ($this->discrepancy_resolution) {
            'ledger_adjustment' => 'Disesuaikan ke Cash Ledger',
            'cashier_covered' => 'Diganti oleh Kasir',
            'manual_resolved' => 'Diselesaikan Manual',
            'catchup_sale' => 'Penjualan Susulan',
            default => '-',
        };
    }

    /**
     * Recalculate net payment totals and expected cash for an open shift.
     * Subtract change_amount from cash payments so tendered amounts never inflate expected drawer cash.
     */
    public function recalculateTotals(): self
    {
        if ($this->isClosed()) {
            return $this;
        }

        $sales = $this->sales()
            ->with('payments')
            ->get();

        $totalCashSales = 0.0;
        $totalQrisSales = 0.0;
        $totalTransferSales = 0.0;
        $totalCashRefund = 0.0;

        foreach ($sales as $sale) {
            $cashGross = (float) $sale->payments
                ->where('payment_method', 'cash')
                ->sum('amount');
            $cashNet = max(0.0, $cashGross - (float) $sale->change_amount);

            if ($sale->status === 'completed') {
                $totalCashSales += $cashNet;
                $totalQrisSales += (float) $sale->payments
                    ->where('payment_method', 'qris')
                    ->sum('amount');
                $totalTransferSales += (float) $sale->payments
                    ->where('payment_method', 'transfer')
                    ->sum('amount');
            } elseif ($sale->status === 'void') {
                $totalCashRefund += $cashNet;
            }
        }

        $totalSales = $totalCashSales + $totalQrisSales + $totalTransferSales;
        $expectedCash = (float) $this->starting_cash + $totalCashSales;

        $this->update([
            'total_cash_sales' => $totalCashSales,
            'total_qris_sales' => $totalQrisSales,
            'total_transfer_sales' => $totalTransferSales,
            'total_sales' => $totalSales,
            'total_cash_refund' => $totalCashRefund,
            'expected_cash' => $expectedCash,
            'cash_discrepancy' => $this->actual_cash !== null
                ? (float) $this->actual_cash - $expectedCash
                : null,
        ]);

        return $this;
    }
}
