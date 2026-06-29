<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $validated = $request->validate([
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_method' => ['nullable', Rule::in(['cash', 'qris', 'transfer'])],
            'status'         => ['nullable', Rule::in(['completed', 'void'])],
        ]);

        $startDate     = $validated['start_date'] ?? Carbon::today()->toDateString();
        $endDate       = $validated['end_date'] ?? Carbon::today()->toDateString();
        $paymentMethod = $validated['payment_method'] ?? null;
        $status        = $validated['status'] ?? null;

        $baseQuery = fn () => Sale::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->when($paymentMethod, fn ($q) => $q->where('payment_method', $paymentMethod));

        $sales = $baseQuery()
            ->with('soldBy')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $completedQuery = fn () => $baseQuery()->where('status', 'completed');
        $voidQuery      = fn () => $baseQuery()->where('status', 'void');

        $totalTransactions     = $completedQuery()->count();
        $totalSales            = $completedQuery()->sum('total');
        $totalVoidTransactions = $voidQuery()->count();
        $totalVoidAmount       = $voidQuery()->sum('total');

        $totalGrossProfit = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate)
            ->where('sales.status', 'completed')
            ->when($paymentMethod, fn ($q) => $q->where('sales.payment_method', $paymentMethod))
            ->selectRaw('SUM(sale_items.subtotal - (sale_items.qty * sale_items.cost_price)) as gross_profit')
            ->value('gross_profit') ?? 0;

        $paymentSummary = $completedQuery()
            ->select('payment_method', DB::raw('COUNT(*) as total_transactions'), DB::raw('SUM(total) as total_amount'))
            ->groupBy('payment_method')
            ->get();

        return view('admin.reports.sales', compact(
            'sales',
            'startDate',
            'endDate',
            'paymentMethod',
            'status',
            'totalTransactions',
            'totalSales',
            'totalGrossProfit',
            'totalVoidTransactions',
            'totalVoidAmount',
            'paymentSummary',
        ));
    }
}