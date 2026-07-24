@extends('layouts.admin')

@section('title', 'Laporan Stok')
@section('page-title', 'Laporan Stok')
@section('page-subtitle', 'Pantau jumlah persediaan, sinkronisasi batch FIFO, nilai modal, dan potensi penjualan.')

@section('page-actions')
    <a href="{{ route('admin.inventory.batches') }}" class="btn btn-outline-secondary">
        Batch FIFO
    </a>

    <a href="{{ route('admin.reports.inventory.export', request()->query()) }}" class="btn btn-success">
        Export Excel
    </a>
@endsection

@section('content')
    @php
        $resolvedCategoryId = request('category_id', $categoryId);
        $resolvedStatus = request('status', $status);

        $filterKeys = ['category_id', 'status'];

        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();

        $hasActiveFilters = $activeFilterCount > 0;

        $selectedCategory = $resolvedCategoryId ? $categories->firstWhere('id', (int) $resolvedCategoryId) : null;

        $statusLabels = [
            'active' => 'Produk Aktif',
            'inactive' => 'Produk Nonaktif',
            'low_stock' => 'Stok Menipis',
            'out_of_stock' => 'Stok Habis',
        ];

        $stockDifference = (int) $totalSystemStock - (int) $totalFifoStockQty;

        $lastCostValue = (float) ($totalLastCostStockValue ?? 0);

        $fifoValue = (float) ($totalFifoStockValue ?? 0);

        $valueDifference = $lastCostValue - $fifoValue;

        $stockSyncPercentage =
            $totalSystemStock > 0
                ? min(100, max(0, ($totalFifoStockQty / $totalSystemStock) * 100))
                : ($totalFifoStockQty === 0
                    ? 100
                    : 0);

        $pageProducts = $products->getCollection();

        $pageSystemStock = $pageProducts->sum(fn($product) => (int) $product->stock);

        $pageFifoStock = $pageProducts->sum(fn($product) => (int) ($product->fifo_stock_qty ?? 0));

        $pageFifoValue = $pageProducts->sum(fn($product) => (float) ($product->fifo_stock_value ?? 0));

        $pageSalesValue = $pageProducts->sum(
            fn($product) => (int) ($product->fifo_stock_qty ?? 0) * (float) $product->price,
        );

        $pageProfitValue = $pageSalesValue - $pageFifoValue;
    @endphp

    <div class="crud-page inventory-report-page">

        {{-- Summary utama --}}
        <section class="inventory-report-summary">
            <article class="inventory-report-summary-card">
                <span>Total Produk</span>

                <strong class="is-primary">
                    {{ number_format($totalProducts, 0, ',', '.') }}
                </strong>

                <small>
                    Produk sesuai filter laporan
                </small>
            </article>

            <article class="inventory-report-summary-card">
                <span>Stok Sistem</span>

                <strong>
                    {{ number_format($totalSystemStock, 0, ',', '.') }}
                    unit
                </strong>

                <small>
                    Total dari kolom stok produk
                </small>
            </article>

            <article class="inventory-report-summary-card">
                <span>Stok Batch FIFO</span>

                <strong class="is-success">
                    {{ number_format($totalFifoStockQty, 0, ',', '.') }}
                    unit
                </strong>

                <small>
                    Total qty remaining seluruh batch
                </small>
            </article>

            <article class="inventory-report-summary-card">
                <span>Produk Tidak Sinkron</span>

                <strong
                    class="{{ $stockMismatchCount > 0 ? 'is-danger' : 'is-success' }}">
                    {{ number_format($stockMismatchCount, 0, ',', '.') }}
                </strong>

                <small>
                    Kondisi ideal adalah 0 produk
                </small>
            </article>
        </section>

        {{-- Filter --}}
        <section class="crud-panel inventory-report-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Laporan</h2>

                    <p>
                        Saring laporan berdasarkan kategori dan kondisi
                        produk atau persediaan.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.reports.inventory') }}" method="GET">
                <div class="inventory-report-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="category_id" class="form-label">
                                Kategori
                            </label>

                            <select name="category_id" id="category_id"
                                class="form-select crud-form-control
                                    @error('category_id') is-invalid @enderror">
                                <option value="">
                                    Semua kategori
                                </option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $resolvedCategoryId === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('category_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label">
                                Status Produk
                            </label>

                            <select name="status" id="status"
                                class="form-select crud-form-control
                                    @error('status') is-invalid @enderror">
                                <option value="">
                                    Semua status
                                </option>

                                <option value="active" @selected($resolvedStatus === 'active')>
                                    Produk aktif
                                </option>

                                <option value="inactive" @selected($resolvedStatus === 'inactive')>
                                    Produk nonaktif
                                </option>

                                <option value="low_stock" @selected($resolvedStatus === 'low_stock')>
                                    Stok menipis
                                </option>

                                <option value="out_of_stock" @selected($resolvedStatus === 'out_of_stock')>
                                    Stok habis
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="inventory-report-filter-footer">
                    <div class="inventory-report-filter-description">
                        <span>Data laporan</span>

                        <strong>
                            {{ number_format($products->total(), 0, ',', '.') }}
                            produk ditemukan
                        </strong>
                    </div>

                    <div class="inventory-report-filter-actions">
                        <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Tampilkan Laporan
                        </button>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="inventory-report-active-filters">
                    <span class="inventory-report-filter-label">
                        Filter:
                    </span>

                    @if ($selectedCategory)
                        <span class="inventory-report-filter-chip">
                            Kategori: {{ $selectedCategory->name }}
                        </span>
                    @endif

                    @if ($resolvedStatus)
                        <span class="inventory-report-filter-chip">
                            Status:
                            {{ $statusLabels[$resolvedStatus] ?? $resolvedStatus }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Peringatan sinkronisasi --}}
        @if ($stockMismatchCount > 0)
            <div class="inventory-report-sync-warning">
                <div>
                    <strong>
                        Ditemukan {{ $stockMismatchCount }} produk
                        dengan stok tidak sinkron
                    </strong>

                    <p>
                        Stok pada tabel produk berbeda dengan total qty
                        remaining pada batch FIFO. Lakukan pemeriksaan sebelum
                        memproses transaksi atau laporan keuangan.
                    </p>
                </div>

                <a href="{{ route('admin.inventory.batches') }}" class="btn btn-outline-danger btn-sm">
                    Periksa Batch FIFO
                </a>
            </div>
        @endif

        {{-- Rekonsiliasi stok --}}
        <section class="crud-panel inventory-reconciliation-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Rekonsiliasi Persediaan</h2>

                    <p>
                        Bandingkan jumlah stok dan nilai modal antara data
                        produk dengan lapisan batch FIFO.
                    </p>
                </div>

                <span
                    class="crud-count-badge
                        {{ $stockDifference === 0 ? 'is-success' : 'is-danger' }}">
                    {{ $stockDifference === 0 ? 'Sinkron' : 'Ada selisih' }}
                </span>
            </div>

            <div class="inventory-reconciliation-grid">
                <article class="inventory-reconciliation-card">
                    <div class="inventory-reconciliation-header">
                        <div>
                            <span>Rekonsiliasi Kuantitas</span>

                            <strong>
                                Sistem vs Batch FIFO
                            </strong>
                        </div>

                        <span
                            class="inventory-reconciliation-status
                                {{ $stockDifference === 0 ? 'is-success' : 'is-danger' }}">
                            {{ $stockDifference === 0 ? 'Sesuai' : 'Perlu diperiksa' }}
                        </span>
                    </div>

                    <div class="inventory-comparison-values">
                        <div>
                            <span>Stok Sistem</span>

                            <strong>
                                {{ number_format($totalSystemStock, 0, ',', '.') }}
                            </strong>

                            <small>unit</small>
                        </div>

                        <div>
                            <span>Stok FIFO</span>

                            <strong>
                                {{ number_format($totalFifoStockQty, 0, ',', '.') }}
                            </strong>

                            <small>unit</small>
                        </div>

                        <div>
                            <span>Selisih</span>

                            <strong
                                class="{{ $stockDifference === 0 ? 'is-success' : 'is-danger' }}">
                                {{ $stockDifference > 0 ? '+' : '' }}
                                {{ number_format($stockDifference, 0, ',', '.') }}
                            </strong>

                            <small>unit</small>
                        </div>
                    </div>

                    <div class="inventory-sync-progress">
                        <div>
                            <span>Sinkronisasi qty</span>

                            <strong>
                                {{ number_format($stockSyncPercentage, 1, ',', '.') }}%
                            </strong>
                        </div>

                        <div class="inventory-sync-progress-bar">
                            <span style="width:
                                    {{ $stockSyncPercentage }}%"></span>
                        </div>
                    </div>

                    <p class="inventory-reconciliation-note">
                        Selisih ideal adalah 0 unit. Perbedaan menandakan
                        stok produk dan batch FIFO belum sinkron.
                    </p>
                </article>

                <article class="inventory-reconciliation-card">
                    <div class="inventory-reconciliation-header">
                        <div>
                            <span>Rekonsiliasi Nilai Modal</span>

                            <strong>
                                Last Cost vs FIFO
                            </strong>
                        </div>

                        <span
                            class="inventory-reconciliation-status
                                {{ $valueDifference === 0.0 ? 'is-success' : 'is-warning' }}">
                            {{ $valueDifference === 0.0 ? 'Nilai sama' : 'Metode berbeda' }}
                        </span>
                    </div>

                    <div class="inventory-value-comparison">
                        <div>
                            <span>Nilai Modal FIFO</span>

                            <strong>
                                Rp
                                {{ number_format($fifoValue, 0, ',', '.') }}
                            </strong>

                            <small>
                                Qty batch × harga modal batch
                            </small>
                        </div>

                        <div>
                            <span>Nilai Last Cost</span>

                            <strong>
                                Rp
                                {{ number_format($lastCostValue, 0, ',', '.') }}
                            </strong>

                            <small>
                                Stok produk × modal terakhir
                            </small>
                        </div>

                        <div>
                            <span>Selisih Nilai</span>

                            <strong
                                class="{{ $valueDifference === 0.0 ? 'is-success' : 'is-warning' }}">
                                Rp
                                {{ number_format($valueDifference, 0, ',', '.') }}
                            </strong>

                            <small>
                                Last cost dikurangi FIFO
                            </small>
                        </div>
                    </div>

                    <p class="inventory-reconciliation-note">
                        Nilai FIFO menjadi nilai modal persediaan yang lebih
                        akurat karena mengikuti harga setiap batch pembelian.
                    </p>
                </article>
            </div>
        </section>

        {{-- Ringkasan nilai --}}
        <section class="inventory-value-summary">
            <article>
                <span>Nilai Modal FIFO</span>

                <strong class="is-success">
                    Rp {{ number_format($fifoValue, 0, ',', '.') }}
                </strong>

                <small>
                    Nilai persediaan berdasarkan batch aktif
                </small>
            </article>

            <article>
                <span>Total Qty FIFO</span>

                <strong class="is-primary">
                    {{ number_format($totalFifoStockQty, 0, ',', '.') }}
                    unit
                </strong>

                <small>
                    Qty yang masih tersedia untuk dijual
                </small>
            </article>

            <article>
                <span>Selisih Sistem dan FIFO</span>

                <strong
                    class="{{ $stockDifference === 0 ? 'is-success' : 'is-danger' }}">
                    {{ $stockDifference > 0 ? '+' : '' }}
                    {{ number_format($stockDifference, 0, ',', '.') }}
                    unit
                </strong>

                <small>
                    Target ideal adalah 0 unit
                </small>
            </article>
        </section>

        {{-- Detail produk --}}
        <section class="crud-panel inventory-report-detail-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Detail Stok Produk</h2>

                    <p>
                        Informasi jumlah stok, modal FIFO, estimasi penjualan,
                        dan potensi laba setiap produk.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($products->total(), 0, ',', '.') }}
                    produk
                </span>
            </div>

            <div class="inventory-report-page-summary">
                <div>
                    <span>Produk Ditampilkan</span>

                    <strong>
                        {{ number_format($products->count(), 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Stok Sistem</span>

                    <strong>
                        {{ number_format($pageSystemStock, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Stok FIFO</span>

                    <strong>
                        {{ number_format($pageFifoStock, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Nilai Modal FIFO</span>

                    <strong>
                        Rp
                        {{ number_format($pageFifoValue, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Potensi Laba</span>

                    <strong class="is-success">
                        Rp
                        {{ number_format($pageProfitValue, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table crud-table inventory-report-detail-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga dan Modal</th>
                            <th>Persediaan</th>
                            <th>Status</th>
                            <th>Nilai Modal FIFO</th>
                            <th>Estimasi Penjualan</th>
                            <th>Potensi Laba</th>
                            <th>Update Terakhir</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $fifoStockQty = (int) ($product->fifo_stock_qty ?? 0);

                                $fifoStockValue = (float) ($product->fifo_stock_value ?? 0);

                                $fifoAverageCost = (float) ($product->fifo_average_cost ?? 0);

                                $systemStock = (int) $product->stock;

                                $stockSalesValue = $fifoStockQty * (float) $product->price;

                                $stockProfitValue = $stockSalesValue - $fifoStockValue;

                                $hasStockMismatch = $systemStock !== $fifoStockQty;

                                $stockGap = $systemStock - $fifoStockQty;

                                $stockStatus = match (true) {
                                    $systemStock <= 0 => [
                                        'label' => 'Stok Habis',
                                        'class' => 'is-danger',
                                    ],

                                    $systemStock <= $product->minimum_stock => [
                                        'label' => 'Stok Menipis',
                                        'class' => 'is-warning',
                                    ],

                                    default => [
                                        'label' => 'Stok Aman',
                                        'class' => 'is-success',
                                    ],
                                };
                            @endphp

                            <tr @class([
                                'has-stock-mismatch' => $hasStockMismatch,
                                'is-inactive' => !$product->is_active,
                            ])>
                                <td>
                                    <div class="inventory-report-product-cell">
                                        <strong>
                                            {{ $product->name }}
                                        </strong>

                                        <span>
                                            SKU {{ $product->sku ?? '-' }}
                                        </span>

                                        <small>
                                            {{ $product->category?->name ?? 'Tanpa kategori' }}
                                        </small>

                                        @if (!$product->is_active)
                                            <span class="inventory-product-inactive">
                                                Produk nonaktif
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="inventory-price-comparison">
                                        <div>
                                            <span>Modal Terakhir</span>

                                            <strong>
                                                Rp
                                                {{ number_format($product->cost_price, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Rata-rata FIFO</span>

                                            <strong>
                                                Rp
                                                {{ number_format($fifoAverageCost, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Harga Jual</span>

                                            <strong>
                                                Rp
                                                {{ number_format($product->price, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="inventory-stock-comparison">
                                        <div>
                                            <span>Sistem</span>

                                            <strong>
                                                {{ number_format($systemStock, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>FIFO</span>

                                            <strong>
                                                {{ number_format($fifoStockQty, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Minimum</span>

                                            <strong>
                                                {{ number_format($product->minimum_stock, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>

                                    @if ($hasStockMismatch)
                                        <div class="inventory-stock-mismatch">
                                            Selisih:
                                            <strong>
                                                {{ $stockGap > 0 ? '+' : '' }}
                                                {{ number_format($stockGap, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="inventory-status-list">
                                        <span
                                            class="crud-status
                                                {{ $stockStatus['class'] }}">
                                            {{ $stockStatus['label'] }}
                                        </span>

                                        <span
                                            class="crud-status
                                                {{ $product->is_active ? 'is-success' : 'is-muted' }}">
                                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="inventory-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($fifoStockValue, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            {{ number_format($fifoStockQty, 0, ',', '.') }}
                                            unit FIFO
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="inventory-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($stockSalesValue, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            Jika seluruh stok FIFO terjual
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="inventory-money-cell">
                                        <strong
                                            class="{{ $stockProfitValue >= 0 ? 'is-success' : 'is-danger' }}">
                                            Rp
                                            {{ number_format($stockProfitValue, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            Estimasi laba kotor
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $product->lastStockUpdatedBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $product->last_stock_updated_at?->format('d M Y H:i') ?? 'Belum ada update' }}
                                        </small>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crud-empty-state">
                                        @if ($hasActiveFilters)
                                            <strong>Produk tidak ditemukan</strong>

                                            <p>
                                                Tidak ada produk yang sesuai
                                                dengan filter laporan tersebut.
                                            </p>

                                            <a href="{{ route('admin.reports.inventory') }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Reset Filter
                                            </a>
                                        @else
                                            <strong>Belum ada data produk</strong>

                                            <p>
                                                Produk yang telah ditambahkan
                                                akan muncul pada laporan stok.
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($products->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="2">
                                    Total halaman ini
                                </th>

                                <th>
                                    <div class="inventory-footer-stock">
                                        <span>
                                            Sistem:
                                            <strong>
                                                {{ number_format($pageSystemStock, 0, ',', '.') }}
                                            </strong>
                                        </span>

                                        <span>
                                            FIFO:
                                            <strong>
                                                {{ number_format($pageFifoStock, 0, ',', '.') }}
                                            </strong>
                                        </span>
                                    </div>
                                </th>

                                <th></th>

                                <th>
                                    Rp
                                    {{ number_format($pageFifoValue, 0, ',', '.') }}
                                </th>

                                <th>
                                    Rp
                                    {{ number_format($pageSalesValue, 0, ',', '.') }}
                                </th>

                                <th>
                                    Rp
                                    {{ number_format($pageProfitValue, 0, ',', '.') }}
                                </th>

                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if ($products->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $products->firstItem() }}
                        sampai {{ $products->lastItem() }}
                        dari {{ $products->total() }} produk
                    </span>

                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
