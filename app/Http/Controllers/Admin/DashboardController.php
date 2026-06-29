<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $todayTransactions = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total');

        $todayGrossProfit = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $today)
            ->where('sales.status', 'completed')
            ->selectRaw('SUM(sale_items.subtotal - (sale_items.qty * sale_items.cost_price)) as gross_profit')
            ->value('gross_profit') ?? 0;

        $todayVoidTransactions = Sale::whereDate('created_at', $today)
            ->where('status', 'void')
            ->count();

        $todayVoidAmount = Sale::whereDate('created_at', $today)
            ->where('status', 'void')
            ->sum('total');

        $totalProducts = Product::where('is_active', true)
            ->count();

        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        $outOfStockProducts = Product::where('is_active', true)
            ->where('stock', 0)
            ->count();

        $averageTransaction = $todayTransactions > 0
            ? $todaySales / $todayTransactions
            : 0;

        $lowStockList = Product::with([
                'category',
                'lastStockUpdatedBy',
            ])
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock', 'asc')
            ->take(10)
            ->get();

        $latestSales = Sale::with('soldBy')
            ->latest()
            ->take(10)
            ->get();

        $paymentSummary = Sale::select(
                'payment_method',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total) as total_amount')
            )
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->get();

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
            'totalProducts',
            'lowStockProducts',
            'outOfStockProducts',
            'todaySales',
            'todayGrossProfit',
            'todayVoidTransactions',
            'todayVoidAmount',
            'averageTransaction',
            'lowStockList',
            'latestSales',
            'paymentSummary',
            'topProductsToday'
        ));
    }
}