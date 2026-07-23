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
use App\Models\SalePayment;
use App\Models\StockBatch;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $validated = $request->validate([
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'qris',
                    'transfer',
                    'split',
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'completed',
                    'void',
                ]),
            ],
        ]);

        $startDate = $validated['start_date']
            ?? Carbon::today()->toDateString();

        $endDate = $validated['end_date']
            ?? Carbon::today()->toDateString();

        $paymentMethod = $validated['payment_method'] ?? null;
        $status = $validated['status'] ?? null;

        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Base Query Penjualan
        |--------------------------------------------------------------------------
        */

        $baseQuery = fn () => Sale::query()
            ->whereBetween('created_at', [
                $startDateTime,
                $endDateTime,
            ])
            ->when(
                $paymentMethod,
                fn ($query) => $query->where(
                    'payment_method',
                    $paymentMethod
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Detail Transaksi
        |--------------------------------------------------------------------------
        */

        $sales = $baseQuery()
            ->with([
                'soldBy',
                'payments',
            ])
            ->when(
                $status,
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Query Completed dan Void
        |--------------------------------------------------------------------------
        */

        $completedQuery = fn () => $baseQuery()
            ->where('status', 'completed');

        $voidQuery = fn () => $baseQuery()
            ->where('status', 'void');

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Penjualan
        |--------------------------------------------------------------------------
        */

        $totalTransactions = $completedQuery()->count();

        $totalSales = $completedQuery()->sum('total');

        $totalVoidTransactions = $voidQuery()->count();

        $totalVoidAmount = $voidQuery()->sum('total');

        /*
        |--------------------------------------------------------------------------
        | Total Laba Kotor
        |--------------------------------------------------------------------------
        */

        $totalGrossProfit = SaleItem::query()
            ->join(
                'sales',
                'sales.id',
                '=',
                'sale_items.sale_id'
            )
            ->whereBetween('sales.created_at', [
                $startDateTime,
                $endDateTime,
            ])
            ->where('sales.status', 'completed')
            ->when(
                $paymentMethod,
                fn ($query) => $query->where(
                    'sales.payment_method',
                    $paymentMethod
                )
            )
            ->selectRaw('
                COALESCE(
                    SUM(
                        sale_items.subtotal
                        - (
                            sale_items.qty
                            * sale_items.cost_price
                        )
                    ),
                    0
                ) as gross_profit
            ')
            ->value('gross_profit') ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Metode Pembayaran
        |--------------------------------------------------------------------------
        */

        $paymentSummary = $completedQuery()
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
                END
            ")
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Rincian Pembayaran Split
        |--------------------------------------------------------------------------
        */

        $splitPaymentBreakdown = collect();

        if (
            $paymentMethod === null
            || $paymentMethod === 'split'
        ) {
            $splitPaymentBreakdown = SalePayment::query()
                ->join(
                    'sales',
                    'sales.id',
                    '=',
                    'sale_payments.sale_id'
                )
                ->whereBetween('sales.created_at', [
                    $startDateTime,
                    $endDateTime,
                ])
                ->where('sales.status', 'completed')
                ->where('sales.payment_method', 'split')
                ->selectRaw('
                    sale_payments.payment_method,
                    SUM(sale_payments.amount) as total_amount
                ')
                ->groupBy('sale_payments.payment_method')
                ->pluck(
                    'total_amount',
                    'payment_method'
                );

            /*
             * Kembalian pada transaksi split berasal dari bagian cash.
             * Karena itu, total kembalian harus mengurangi nominal cash
             * yang benar-benar diterima toko.
             */

            $totalSplitChange = Sale::query()
                ->whereBetween('created_at', [
                    $startDateTime,
                    $endDateTime,
                ])
                ->where('status', 'completed')
                ->where('payment_method', 'split')
                ->sum('change_amount');

            $splitCashAmount = max(
                0,
                (float) $splitPaymentBreakdown->get('cash', 0)
                    - (float) $totalSplitChange
            );

            $splitPaymentBreakdown->put(
                'cash',
                $splitCashAmount
            );
        }

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
            'splitPaymentBreakdown',
        ));
    }

    public function exportSales(Request $request)
    {
        $validated = $request->validate([
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'qris',
                    'transfer',
                    'split',
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'completed',
                    'void',
                ]),
            ],
        ]);

        $startDate = $validated['start_date']
            ?? Carbon::today()->toDateString();

        $endDate = $validated['end_date']
            ?? Carbon::today()->toDateString();

        $paymentMethod = $validated['payment_method'] ?? null;
        $status = $validated['status'] ?? null;

        $fileName = 'laporan-penjualan-'
            .$startDate
            .'-sd-'
            .$endDate
            .'.xlsx';

        return Excel::download(
            new SalesReportExport(
                $startDate,
                $endDate,
                $paymentMethod,
                $status
            ),
            $fileName
        );
    }

    public function inventory(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists(
                    'categories',
                    'id'
                )->whereNull('deleted_at'),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'low_stock',
                    'out_of_stock',
                ]),
            ],
        ]);

        $categoryId = $validated['category_id'] ?? null;
        $status = $validated['status'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Batch FIFO per Produk
        |--------------------------------------------------------------------------
        */

        $batchSummary = StockBatch::query()
            ->select('product_id')
            ->selectRaw('
                COALESCE(
                    SUM(qty_remaining),
                    0
                ) as fifo_stock_qty
            ')
            ->selectRaw('
                COALESCE(
                    SUM(qty_remaining * cost_price),
                    0
                ) as fifo_stock_value
            ')
            ->where('qty_remaining', '>', 0)
            ->groupBy('product_id');

        /*
        |--------------------------------------------------------------------------
        | Filter Status Produk
        |--------------------------------------------------------------------------
        */

        $applyStatusFilter = function ($query) use ($status) {
            match ($status) {
                'active' => $query->where(
                    'products.is_active',
                    true
                ),

                'inactive' => $query->where(
                    'products.is_active',
                    false
                ),

                'low_stock' => $query
                    ->whereColumn(
                        'products.stock',
                        '<=',
                        'products.minimum_stock'
                    )
                    ->where(
                        'products.stock',
                        '>',
                        0
                    ),

                'out_of_stock' => $query->where(
                    'products.stock',
                    0
                ),

                default => null,
            };
        };

        /*
        |--------------------------------------------------------------------------
        | Base Query Inventory
        |--------------------------------------------------------------------------
        */

        $baseQuery = Product::query()
            ->leftJoinSub(
                $batchSummary,
                'fifo_batches',
                fn ($join) => $join->on(
                    'fifo_batches.product_id',
                    '=',
                    'products.id'
                )
            )
            ->when(
                $categoryId,
                fn ($query) => $query->where(
                    'products.category_id',
                    $categoryId
                )
            )
            ->when(
                $status,
                $applyStatusFilter
            );

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Inventory
        |--------------------------------------------------------------------------
        */

        $totalProducts = (clone $baseQuery)
            ->count('products.id');

        $totalSystemStock = (clone $baseQuery)
            ->sum('products.stock');

        $totalFifoStockQty = (clone $baseQuery)
            ->selectRaw('
                COALESCE(
                    SUM(
                        COALESCE(
                            fifo_batches.fifo_stock_qty,
                            0
                        )
                    ),
                    0
                ) as total_qty
            ')
            ->value('total_qty') ?? 0;

        $totalFifoStockValue = (clone $baseQuery)
            ->selectRaw('
                COALESCE(
                    SUM(
                        COALESCE(
                            fifo_batches.fifo_stock_value,
                            0
                        )
                    ),
                    0
                ) as total_value
            ')
            ->value('total_value') ?? 0;

        $totalLastCostStockValue = (clone $baseQuery)
            ->selectRaw('
                COALESCE(
                    SUM(
                        products.stock
                        * products.cost_price
                    ),
                    0
                ) as total_value
            ')
            ->value('total_value') ?? 0;

        $stockMismatchCount = (clone $baseQuery)
            ->whereRaw('
                products.stock
                <>
                COALESCE(
                    fifo_batches.fifo_stock_qty,
                    0
                )
            ')
            ->count('products.id');

        /*
        |--------------------------------------------------------------------------
        | Detail Produk
        |--------------------------------------------------------------------------
        */

        $products = (clone $baseQuery)
            ->with('category')
            ->select('products.*')
            ->selectRaw('
                COALESCE(
                    fifo_batches.fifo_stock_qty,
                    0
                ) as fifo_stock_qty
            ')
            ->selectRaw('
                COALESCE(
                    fifo_batches.fifo_stock_value,
                    0
                ) as fifo_stock_value
            ')
            ->selectRaw('
                CASE
                    WHEN COALESCE(
                        fifo_batches.fifo_stock_qty,
                        0
                    ) > 0
                    THEN COALESCE(
                        fifo_batches.fifo_stock_value,
                        0
                    ) / COALESCE(
                        fifo_batches.fifo_stock_qty,
                        0
                    )
                    ELSE 0
                END as fifo_average_cost
            ')
            ->orderBy('products.name')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Ringkasan per Kategori
        |--------------------------------------------------------------------------
        */

        $categorySummary = Product::query()
            ->leftJoin(
                'categories',
                'categories.id',
                '=',
                'products.category_id'
            )
            ->leftJoinSub(
                $batchSummary,
                'fifo_batches',
                fn ($join) => $join->on(
                    'fifo_batches.product_id',
                    '=',
                    'products.id'
                )
            )
            ->when(
                $categoryId,
                fn ($query) => $query->where(
                    'products.category_id',
                    $categoryId
                )
            )
            ->when(
                $status,
                $applyStatusFilter
            )
            ->selectRaw('
                COALESCE(
                    categories.name,
                    "Tanpa Kategori"
                ) as category_name,

                COUNT(
                    products.id
                ) as total_products,

                COALESCE(
                    SUM(products.stock),
                    0
                ) as total_system_stock,

                COALESCE(
                    SUM(
                        COALESCE(
                            fifo_batches.fifo_stock_qty,
                            0
                        )
                    ),
                    0
                ) as total_fifo_stock,

                COALESCE(
                    SUM(
                        COALESCE(
                            fifo_batches.fifo_stock_value,
                            0
                        )
                    ),
                    0
                ) as total_fifo_value
            ')
            ->groupBy(
                'categories.id',
                'categories.name'
            )
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
                Rule::exists(
                    'categories',
                    'id'
                )->whereNull('deleted_at'),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'low_stock',
                    'out_of_stock',
                ]),
            ],
        ]);

        $categoryId = ! empty(
            $validated['category_id']
        )
            ? (int) $validated['category_id']
            : null;

        $status = $validated['status'] ?? null;

        $fileName = 'laporan-stok-fifo-'
            .now()->format('Ymd-His')
            .'.xlsx';

        return Excel::download(
            new InventoryReportExport(
                $categoryId,
                $status
            ),
            $fileName
        );
    }

    public function purchases(Request $request)
    {
        $validated = $request->validate([
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists(
                    'suppliers',
                    'id'
                )->whereNull('deleted_at'),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'completed',
                    'void',
                ]),
            ],
        ]);

        $startDate = $validated['start_date']
            ?? now()->startOfMonth()->toDateString();

        $endDate = $validated['end_date']
            ?? now()->toDateString();

        $supplierId = $validated['supplier_id'] ?? null;
        $status = $validated['status'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Base Query Pembelian
        |--------------------------------------------------------------------------
        */

        $baseQuery = fn () => Purchase::query()
            ->whereDate(
                'purchase_date',
                '>=',
                $startDate
            )
            ->whereDate(
                'purchase_date',
                '<=',
                $endDate
            )
            ->when(
                $supplierId,
                fn ($query) => $query->where(
                    'supplier_id',
                    $supplierId
                )
            );

        $tableQuery = fn () => $baseQuery()
            ->when(
                $status,
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            );

        $completedQuery = fn () => $baseQuery()
            ->where('status', 'completed');

        $voidQuery = fn () => $baseQuery()
            ->where('status', 'void');

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Pembelian
        |--------------------------------------------------------------------------
        */

        $totalPurchaseTransactions = $completedQuery()
            ->count();

        $totalPurchaseAmount = $completedQuery()
            ->sum('total');

        $totalPurchaseItems = $completedQuery()
            ->join(
                'purchase_items',
                'purchase_items.purchase_id',
                '=',
                'purchases.id'
            )
            ->sum('purchase_items.qty');

        $totalVoidTransactions = $voidQuery()
            ->count();

        $totalVoidAmount = $voidQuery()
            ->sum('total');

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Supplier
        |--------------------------------------------------------------------------
        */

        $supplierSummary = $completedQuery()
            ->join(
                'suppliers',
                'suppliers.id',
                '=',
                'purchases.supplier_id'
            )
            ->selectRaw('
                suppliers.name as supplier_name,
                COUNT(purchases.id) as total_transactions,
                SUM(purchases.total) as total_amount
            ')
            ->groupBy(
                'suppliers.id',
                'suppliers.name'
            )
            ->orderByDesc('total_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Detail Pembelian
        |--------------------------------------------------------------------------
        */

        $purchases = $tableQuery()
            ->with([
                'supplier',
                'createdBy',
            ])
            ->withSum(
                'items as total_qty',
                'qty'
            )
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
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists(
                    'suppliers',
                    'id'
                )->whereNull('deleted_at'),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'completed',
                    'void',
                ]),
            ],
        ]);

        $startDate = $validated['start_date']
            ?? now()->startOfMonth()->toDateString();

        $endDate = $validated['end_date']
            ?? now()->toDateString();

        $supplierId = ! empty(
            $validated['supplier_id']
        )
            ? (int) $validated['supplier_id']
            : null;

        $status = $validated['status'] ?? null;

        $fileName = 'laporan-pembelian-'
            .$startDate
            .'-sd-'
            .$endDate
            .'.xlsx';

        return Excel::download(
            new PurchaseReportExport(
                $startDate,
                $endDate,
                $supplierId,
                $status
            ),
            $fileName
        );
    }
}
