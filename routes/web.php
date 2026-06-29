<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('/categories-trash', [CategoryController::class, 'trash'])->name('categories.trash');
        Route::put('/categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');

        Route::resource('products', ProductController::class)->except(['show']);
        Route::get('/products-trash', [ProductController::class, 'trash'])->name('products.trash');
        Route::put('/products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');

        Route::get('/inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('/inventory/stock-in', [InventoryController::class, 'storeStockIn'])->name('inventory.stock-in.store');
        Route::get('/inventory/adjustment', [InventoryController::class, 'adjustment'])->name('inventory.adjustment');
        Route::post('/inventory/adjustment', [InventoryController::class, 'storeAdjustment'])->name('inventory.adjustment.store');
        Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('/inventory/logs', [InventoryController::class, 'logs'])->name('inventory.logs');

        Route::get('/sales/pos', [SaleController::class, 'pos'])->name('sales.pos');
        Route::post('/sales/checkout', [SaleController::class, 'checkout'])->name('sales.checkout');
        Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::put('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    });

Route::middleware(['auth', 'role:cashier'])
    ->prefix('cashier')
    ->name('cashier.')
    ->group(function () {
        Route::get('/pos', function () {
            return 'Cashier POS';
        })->name('pos');
    });

require __DIR__ . '/auth.php';