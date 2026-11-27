<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CashierShiftController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Finance\FinanceAccountController;
use App\Http\Controllers\Admin\Finance\FinanceCategoryController;
use App\Http\Controllers\Admin\Finance\FinanceDashboardController;
use App\Http\Controllers\Admin\Finance\FinanceReconciliationController;
use App\Http\Controllers\Admin\Finance\FinanceTransactionController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $user = $request->user();

    if ($user) {
        if (! $user->is_active) {
            User::logoutSession($request);

            return redirect()->route('login')->withErrors([
                'email' => 'Akun kamu sudah dinonaktifkan.',
            ]);
        }

        return redirect()->route($user->homeRouteName());
    }

    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Operasional POS (Admin, Supervisor, Cashier)
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:admin,supervisor,cashier')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            // Sales
            Route::get('/sales/pos', [SaleController::class, 'pos'])
                ->name('sales.pos');

            Route::post('/sales/checkout', [SaleController::class, 'checkout'])
                ->name('sales.checkout');

            Route::get('/sales/history', [SaleController::class, 'history'])
                ->name('sales.history');

            Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])
                ->name('sales.receipt');

            Route::put('/sales/{sale}/void', [SaleController::class, 'void'])
                ->name('sales.void');

            // Cashier Shifts
            Route::post('/shifts/open', [CashierShiftController::class, 'open'])
                ->name('shifts.open');

            Route::get('/shifts/current', [CashierShiftController::class, 'current'])
                ->name('shifts.current');

            Route::post('/shifts/{shift}/close', [CashierShiftController::class, 'close'])
                ->name('shifts.close');

            Route::get('/shifts/{shift}/summary', [CashierShiftController::class, 'summary'])
                ->name('shifts.summary');

            // Inventory
            Route::get('/inventory/stock-in', [InventoryController::class, 'stockIn'])
                ->name('inventory.stock-in');

            Route::post('/inventory/stock-in', [InventoryController::class, 'storeStockIn'])
                ->name('inventory.stock-in.store');

            Route::get('/inventory/adjustment', [InventoryController::class, 'adjustment'])
                ->name('inventory.adjustment');

            Route::post('/inventory/adjustment', [InventoryController::class, 'storeAdjustment'])
                ->name('inventory.adjustment.store');

            Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])
                ->name('inventory.low-stock');

            Route::get('/inventory/logs', [InventoryController::class, 'logs'])
                ->name('inventory.logs');

            Route::get('/inventory/batches', [InventoryController::class, 'batches'])
                ->name('inventory.batches');

            // Reports
            Route::get('/reports/sales', [ReportController::class, 'sales'])
                ->name('reports.sales');

            Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])
                ->name('reports.sales.export');

            Route::get('/reports/inventory', [ReportController::class, 'inventory'])
                ->name('reports.inventory');

            Route::get('/reports/inventory/export', [ReportController::class, 'exportInventory'])
                ->name('reports.inventory.export');

            Route::get('/reports/purchases', [ReportController::class, 'purchases'])
                ->name('reports.purchases');

            Route::get('/reports/purchases/export', [ReportController::class, 'exportPurchases'])
                ->name('reports.purchases.export');

            // Purchases
            Route::get('/purchases', [PurchaseController::class, 'index'])
                ->name('purchases.index');

            Route::get('/purchases/create', [PurchaseController::class, 'create'])
                ->name('purchases.create');

            Route::post('/purchases', [PurchaseController::class, 'store'])
                ->name('purchases.store');

            Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])
                ->name('purchases.show');

            Route::put('/purchases/{purchase}/void', [PurchaseController::class, 'void'])
                ->name('purchases.void');
        });

        /*
        |--------------------------------------------------------------------------
        | Supervisor & Admin
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:admin,supervisor')->group(function () {
            // Shift Monitoring & Tutup Shift Kasir
            Route::get('/shifts', [CashierShiftController::class, 'index'])
                ->name('shifts.index');
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Only
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:admin')->group(function () {
            // Categories
            Route::resource('categories', CategoryController::class)
                ->except(['show']);

            Route::get('/categories-trash', [CategoryController::class, 'trash'])
                ->name('categories.trash');

            Route::put('/categories/{id}/restore', [CategoryController::class, 'restore'])
                ->name('categories.restore');

            // Products
            Route::resource('products', ProductController::class)
                ->except(['show']);

            Route::get('/products-trash', [ProductController::class, 'trash'])
                ->name('products.trash');

            Route::put('/products/{id}/restore', [ProductController::class, 'restore'])
                ->name('products.restore');

            // Suppliers
            Route::resource('suppliers', SupplierController::class)
                ->except(['show']);

            Route::get('/suppliers-trash', [SupplierController::class, 'trash'])
                ->name('suppliers.trash');

            Route::put('/suppliers/{id}/restore', [SupplierController::class, 'restore'])
                ->name('suppliers.restore');

            // Users
            Route::resource('users', UserController::class)
                ->except(['show']);

            Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('users.reset-password');

            // Shift Settlement & Catch-Up Sale (Admin Only)
            Route::post('/shifts/{shift}/resolve-discrepancy', [CashierShiftController::class, 'resolveDiscrepancy'])
                ->name('shifts.resolve-discrepancy');

            Route::get('/shifts/{shift}/catchup-sale', [CashierShiftController::class, 'createCatchupSale'])
                ->name('shifts.catchup-sale.create');

            Route::post('/shifts/{shift}/catchup-sale', [CashierShiftController::class, 'storeCatchupSale'])
                ->name('shifts.catchup-sale.store');

            // Audit Trail / Activity Logs
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])
                ->name('activity-logs.index');

            /*
            |--------------------------------------------------------------------------
            | Finance
            |--------------------------------------------------------------------------
            */

            Route::prefix('finance')
                ->name('finance.')
                ->group(function () {

                    Route::get('/dashboard', [FinanceDashboardController::class, 'index'])
                        ->name('dashboard');

                    Route::resource('accounts', FinanceAccountController::class)
                        ->except(['show']);

                    Route::resource('categories', FinanceCategoryController::class)
                        ->except(['show']);

                    Route::get('/transactions', [FinanceTransactionController::class, 'index'])
                        ->name('transactions.index');

                    Route::get('/transactions/create', [FinanceTransactionController::class, 'create'])
                        ->name('transactions.create');

                    Route::post('/transactions', [FinanceTransactionController::class, 'store'])
                        ->name('transactions.store');

                    Route::get('/transactions/{financeTransaction}', [FinanceTransactionController::class, 'show'])
                        ->name('transactions.show');

                    Route::put('/transactions/{financeTransaction}/void', [FinanceTransactionController::class, 'void'])
                        ->name('transactions.void');

                    Route::get('/reconciliation', [FinanceReconciliationController::class, 'index'])
                        ->name('reconciliation.index');

                    Route::post('/reconciliation/{sourceType}/{sourceId}/repost', [FinanceReconciliationController::class, 'repost'])
                        ->where('sourceType', 'sale|purchase')
                        ->whereNumber('sourceId')
                        ->name('reconciliation.repost');

                    Route::put('/reconciliation/{sourceType}/{sourceId}/sync-void', [FinanceReconciliationController::class, 'syncVoid'])
                        ->where('sourceType', 'sale|purchase')
                        ->whereNumber('sourceId')
                        ->name('reconciliation.sync-void');
                });
        });
    });

require __DIR__.'/auth.php';
