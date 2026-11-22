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
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();
        $todayDateStr = $today->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Sales Summary
        |--------------------------------------------------------------------------
        */
        $salesAggregates = Sale::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->selectRaw("
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as today_transactions,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) as today_sales,
                COUNT(CASE WHEN status = 'void' THEN 1 END) as today_void_transactions,
                COALESCE(SUM(CASE WHEN status = 'void' THEN total ELSE 0 END), 0) as today_void_amount
            ")
            ->first();

        $todayTransactions = (int) ($salesAggregates->today_transactions ?? 0);
        $todaySales = (float) ($salesAggregates->today_sales ?? 0);
        $todayVoidTransactions = (int) ($salesAggregates->today_void_transactions ?? 0);
        $todayVoidAmount = (float) ($salesAggregates->today_void_amount ?? 0);

        $todayGrossProfit = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$todayStart, $todayEnd])
            ->where('sales.status', 'completed')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal - (sale_items.qty * sale_items.cost_price)), 0) as gross_profit')
            ->value('gross_profit') ?? 0;

        $averageTransaction = $todayTransactions > 0
            ? $todaySales / $todayTransactions
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Purchase Summary
        |--------------------------------------------------------------------------
        */
        $purchaseAggregates = Purchase::query()
            ->where('purchase_date', $todayDateStr)
            ->selectRaw("
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as today_purchase_transactions,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) as today_purchase_amount,
                COUNT(CASE WHEN status = 'void' THEN 1 END) as today_void_purchase_transactions,
                COALESCE(SUM(CASE WHEN status = 'void' THEN total ELSE 0 END), 0) as today_void_purchase_amount
            ")
            ->first();

        $todayPurchaseTransactions = (int) ($purchaseAggregates->today_purchase_transactions ?? 0);
        $todayPurchaseAmount = (float) ($purchaseAggregates->today_purchase_amount ?? 0);
        $todayVoidPurchaseTransactions = (int) ($purchaseAggregates->today_void_purchase_transactions ?? 0);
        $todayVoidPurchaseAmount = (float) ($purchaseAggregates->today_void_purchase_amount ?? 0);

        $todayPurchaseQty = Purchase::join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.purchase_date', $todayDateStr)
            ->where('purchases.status', 'completed')
            ->sum('purchase_items.qty');

        /*
        |--------------------------------------------------------------------------
        | Inventory Summary
        |--------------------------------------------------------------------------
        */
        $productStats = Product::where('is_active', true)
            ->selectRaw('
                COUNT(*) as total_products,
                COUNT(CASE WHEN stock <= minimum_stock THEN 1 END) as low_stock_products,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_products
            ')
            ->first();

        $totalProducts = (int) ($productStats->total_products ?? 0);
        $lowStockProducts = (int) ($productStats->low_stock_products ?? 0);
        $outOfStockProducts = (int) ($productStats->out_of_stock_products ?? 0);

        $fifoStats = StockBatch::where('qty_remaining', '>', 0)
            ->selectRaw('
                COALESCE(SUM(qty_remaining), 0) as total_qty,
                COALESCE(SUM(qty_remaining * cost_price), 0) as total_value
            ')
            ->first();

        $totalFifoStockQty = (int) ($fifoStats->total_qty ?? 0);
        $totalFifoStockValue = (float) ($fifoStats->total_value ?? 0);

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
            ->whereBetween('created_at', [$todayStart, $todayEnd])
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
            ->whereBetween('sales.created_at', [$todayStart, $todayEnd])
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
            ->whereBetween('sales.created_at', [$todayStart, $todayEnd])
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
