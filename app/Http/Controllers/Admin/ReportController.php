<?php

namespace App\Http\Controllers\Admin;

use App\Exports\InventoryReportExport;
use App\Exports\PurchaseReportExport;
use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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

    public function exportSales(Request $request)
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
        $fileName      = 'laporan-penjualan-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(new SalesReportExport($startDate, $endDate, $paymentMethod, $status), $fileName);
    }

    public function inventory(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'status'      => ['nullable', Rule::in(['active', 'inactive', 'low_stock', 'out_of_stock'])],
        ]);

        $categoryId = $validated['category_id'] ?? null;
        $status     = $validated['status'] ?? null;

        $batchSummary = StockBatch::query()
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(qty_remaining), 0) as fifo_stock_qty')
            ->selectRaw('COALESCE(SUM(qty_remaining * cost_price), 0) as fifo_stock_value')
            ->where('qty_remaining', '>', 0)
            ->groupBy('product_id');

        $applyStatusFilter = function ($query) use ($status) {
            match ($status) {
                'active'       => $query->where('products.is_active', true),
                'inactive'     => $query->where('products.is_active', false),
                'low_stock'    => $query->whereColumn('products.stock', '<=', 'products.minimum_stock')->where('products.stock', '>', 0),
                'out_of_stock' => $query->where('products.stock', 0),
                default        => null,
            };
        };

        $baseQuery = Product::query()
            ->leftJoinSub($batchSummary, 'fifo_batches', fn ($join) => $join->on('fifo_batches.product_id', '=', 'products.id'))
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->when($status, $applyStatusFilter);

        $totalProducts           = (clone $baseQuery)->count('products.id');
        $totalSystemStock        = (clone $baseQuery)->sum('products.stock');
        $totalFifoStockQty       = (clone $baseQuery)->selectRaw('COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_qty, 0)), 0) as total_qty')->value('total_qty') ?? 0;
        $totalFifoStockValue     = (clone $baseQuery)->selectRaw('COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_value, 0)), 0) as total_value')->value('total_value') ?? 0;
        $totalLastCostStockValue = (clone $baseQuery)->selectRaw('COALESCE(SUM(products.stock * products.cost_price), 0) as total_value')->value('total_value') ?? 0;
        $stockMismatchCount      = (clone $baseQuery)->whereRaw('products.stock <> COALESCE(fifo_batches.fifo_stock_qty, 0)')->count('products.id');

        $products = (clone $baseQuery)
            ->with(['category'])
            ->select('products.*')
            ->selectRaw('COALESCE(fifo_batches.fifo_stock_qty, 0) as fifo_stock_qty')
            ->selectRaw('COALESCE(fifo_batches.fifo_stock_value, 0) as fifo_stock_value')
            ->selectRaw('
                CASE
                    WHEN COALESCE(fifo_batches.fifo_stock_qty, 0) > 0
                    THEN COALESCE(fifo_batches.fifo_stock_value, 0) / COALESCE(fifo_batches.fifo_stock_qty, 0)
                    ELSE 0
                END as fifo_average_cost
            ')
            ->orderBy('products.name')
            ->paginate(15)
            ->withQueryString();

        $categorySummary = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoinSub($batchSummary, 'fifo_batches', fn ($join) => $join->on('fifo_batches.product_id', '=', 'products.id'))
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->when($status, $applyStatusFilter)
            ->selectRaw('
                COALESCE(categories.name, "Tanpa Kategori") as category_name,
                COUNT(products.id) as total_products,
                COALESCE(SUM(products.stock), 0) as total_system_stock,
                COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_qty, 0)), 0) as total_fifo_stock,
                COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_value, 0)), 0) as total_fifo_value
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        $categories = Category::orderBy('name')->get();

        return view('admin.reports.inventory', compact(
            'products',
            'categories',
            'categorySummary',
            'categoryId',
            'status',
            'totalProducts',
            'totalSystemStock',
            'totalFifoStockQty',
            'totalFifoStockValue',
            'totalLastCostStockValue',
            'stockMismatchCount',
        ));
    }

    public function exportInventory(Request $request)
{
    $validated = $request->validate([
        'category_id' => [
            'nullable',
            Rule::exists('categories', 'id')->whereNull('deleted_at'),
        ],
        'status' => [
            'nullable',
            Rule::in(['active', 'inactive', 'low_stock', 'out_of_stock']),
        ],
    ]);

    $categoryId = !empty($validated['category_id'])
        ? (int) $validated['category_id']
        : null;

    $status = $validated['status'] ?? null;

    $fileName = 'laporan-stok-fifo-' . now()->format('Ymd-His') . '.xlsx';

    return Excel::download(
        new InventoryReportExport($categoryId, $status),
        $fileName
    );
}

    public function purchases(Request $request)
    {
        $validated = $request->validate([
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'status'      => ['nullable', Rule::in(['completed', 'void'])],
        ]);

        $startDate  = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate    = $validated['end_date'] ?? now()->toDateString();
        $supplierId = $validated['supplier_id'] ?? null;
        $status     = $validated['status'] ?? null;

        $baseQuery = fn () => Purchase::query()
            ->whereDate('purchase_date', '>=', $startDate)
            ->whereDate('purchase_date', '<=', $endDate)
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));

        $tableQuery     = fn () => $baseQuery()->when($status, fn ($q) => $q->where('status', $status));
        $completedQuery = fn () => $baseQuery()->where('status', 'completed');
        $voidQuery      = fn () => $baseQuery()->where('status', 'void');

        $totalPurchaseTransactions = $completedQuery()->count();
        $totalPurchaseAmount       = $completedQuery()->sum('total');
        $totalPurchaseItems        = $completedQuery()->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')->sum('purchase_items.qty');
        $totalVoidTransactions     = $voidQuery()->count();
        $totalVoidAmount           = $voidQuery()->sum('total');

        $supplierSummary = $completedQuery()
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->selectRaw('suppliers.name as supplier_name, COUNT(purchases.id) as total_transactions, SUM(purchases.total) as total_amount')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_amount')
            ->get();

        $purchases = $tableQuery()
            ->with(['supplier', 'createdBy'])
            ->withSum('items as total_qty', 'qty')
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('admin.reports.purchases', compact(
            'purchases',
            'suppliers',
            'supplierSummary',
            'startDate',
            'endDate',
            'supplierId',
            'status',
            'totalPurchaseTransactions',
            'totalPurchaseAmount',
            'totalPurchaseItems',
            'totalVoidTransactions',
            'totalVoidAmount',
        ));
    }

    public function exportPurchases(Request $request)
    {
        $validated = $request->validate([
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'status'      => ['nullable', Rule::in(['completed', 'void'])],
        ]);

        $startDate  = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate    = $validated['end_date'] ?? now()->toDateString();
        $supplierId = !empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null;
        $status     = $validated['status'] ?? null;
        $fileName   = 'laporan-pembelian-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(new PurchaseReportExport($startDate, $endDate, $supplierId, $status), $fileName);
    }
}