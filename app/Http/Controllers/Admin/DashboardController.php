<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Sales Summary
        |--------------------------------------------------------------------------
        */
        $todayTransactions = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total');

        $todayGrossProfit = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $today)
            ->where('sales.status', 'completed')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal - (sale_items.qty * sale_items.cost_price)), 0) as gross_profit')
            ->value('gross_profit') ?? 0;

        $todayVoidTransactions = Sale::whereDate('created_at', $today)
            ->where('status', 'void')
            ->count();

        $todayVoidAmount = Sale::whereDate('created_at', $today)
            ->where('status', 'void')
            ->sum('total');

        $averageTransaction = $todayTransactions > 0
            ? $todaySales / $todayTransactions
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Purchase Summary
        |--------------------------------------------------------------------------
        */
        $todayPurchaseTransactions = Purchase::whereDate('purchase_date', $today)
            ->where('status', 'completed')
            ->count();

        $todayPurchaseAmount = Purchase::whereDate('purchase_date', $today)
            ->where('status', 'completed')
            ->sum('total');

        $todayPurchaseQty = Purchase::join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereDate('purchases.purchase_date', $today)
            ->where('purchases.status', 'completed')
            ->sum('purchase_items.qty');

        $todayVoidPurchaseTransactions = Purchase::whereDate('purchase_date', $today)
            ->where('status', 'void')
            ->count();

        $todayVoidPurchaseAmount = Purchase::whereDate('purchase_date', $today)
            ->where('status', 'void')
            ->sum('total');

        /*
        |--------------------------------------------------------------------------
        | Inventory Summary
        |--------------------------------------------------------------------------
        */
        $totalProducts = Product::where('is_active', true)
            ->count();

        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        $outOfStockProducts = Product::where('is_active', true)
            ->where('stock', 0)
            ->count();

        $totalFifoStockQty = StockBatch::where('qty_remaining', '>', 0)
            ->sum('qty_remaining');

        $totalFifoStockValue = StockBatch::where('qty_remaining', '>', 0)
            ->selectRaw('COALESCE(SUM(qty_remaining * cost_price), 0) as total_value')
            ->value('total_value') ?? 0;

        $lowStockList = Product::with([
                'category',
                'lastStockUpdatedBy',
            ])
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock', 'asc')
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */
        $latestSales = Sale::with(['soldBy', 'payments'])
            ->latest()
            ->take(10)
            ->get();

        $latestPurchases = Purchase::with(['supplier', 'createdBy'])
            ->latest('purchase_date')
            ->latest('id')
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Payment Summary
        |--------------------------------------------------------------------------
        | Pakai sale_payments agar split payment tetap akurat.
        */
        $paymentSummary = Sale::query()
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->select('payment_method')
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('SUM(total) as total_amount')
            ->groupBy('payment_method')
            ->orderByRaw("
                CASE payment_method
                    WHEN 'cash' THEN 1
                    WHEN 'qris' THEN 2
                    WHEN 'transfer' THEN 3
                    WHEN 'split' THEN 4
                    ELSE 5
                END")
            ->get();
        
        $splitPaymentBreakdown = SalePayment::query()
    ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
    ->whereDate('sales.created_at', $today)
    ->where('sales.status', 'completed')
    ->where('sales.payment_method', 'split')
    ->selectRaw("
        sale_payments.payment_method,
        SUM(
            CASE
                WHEN sale_payments.payment_method = 'cash'
                    THEN sale_payments.amount - sales.change_amount
                ELSE sale_payments.amount
            END
        ) as total_amount
    ")
    ->groupBy('sale_payments.payment_method')
    ->pluck('total_amount', 'payment_method');

        $topProductsToday = SaleItem::select(
                'products.name as product_name',
                DB::raw('SUM(sale_items.qty) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_sales')
            )
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $today)
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'todayTransactions',
            'todaySales',
            'todayGrossProfit',
            'todayVoidTransactions',
            'todayVoidAmount',
            'averageTransaction',

            'todayPurchaseTransactions',
            'todayPurchaseAmount',
            'todayPurchaseQty',
            'todayVoidPurchaseTransactions',
            'todayVoidPurchaseAmount',

            'totalProducts',
            'lowStockProducts',
            'outOfStockProducts',
            'totalFifoStockQty',
            'totalFifoStockValue',

            'lowStockList',
            'latestSales',
            'latestPurchases',
            'paymentSummary',
            'splitPaymentBreakdown',
            'topProductsToday'
        ));
    }
}