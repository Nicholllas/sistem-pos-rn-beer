@extends('layouts.admin')

@section('title', 'Laporan Stok')
@section('page-title', 'Laporan Stok')

@section('content')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Filter Laporan Stok</h3>
        </div>

        <form action="{{ route('admin.reports.inventory') }}" method="GET">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="category_id" class="form-label">
                            Kategori
                        </label>

                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Semua Kategori</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua Status</option>

                            <option value="active" @selected($status === 'active')>
                                Aktif
                            </option>

                            <option value="inactive" @selected($status === 'inactive')>
                                Nonaktif
                            </option>

                            <option value="low_stock" @selected($status === 'low_stock')>
                                Stok Menipis
                            </option>

                            <option value="out_of_stock" @selected($status === 'out_of_stock')>
                                Stok Habis
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100 flex-wrap">
                            <a href="{{ route('admin.reports.inventory') }}" class="btn btn-secondary flex-fill">
                                Reset
                            </a>

                            <a href="{{ route('admin.reports.inventory.export', request()->query()) }}"
                                class="btn btn-success flex-fill">
                                Export Excel
                            </a>

                            <button type="submit" class="btn btn-primary flex-fill">
                                Tampilkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @php
        $stockDifference = $totalSystemStock - $totalFifoStockQty;
        $lastCostValue = $totalLastCostStockValue ?? 0;
        $fifoValue = $totalFifoStockValue ?? 0;
        $valueDifference = $lastCostValue - $fifoValue;
    @endphp

    {{-- Ringkasan Produk --}}
    <div class="mb-3">
        <div class="inventory-section-title">
            Ringkasan Produk
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Total Produk
                    </div>

                    <div class="inventory-summary-value text-primary">
                        {{ $totalProducts }}
                    </div>

                    <div class="inventory-summary-note">
                        Produk sesuai filter laporan.
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Stok Sistem
                    </div>

                    <div class="inventory-summary-value text-info">
                        {{ $totalSystemStock }}
                    </div>

                    <div class="inventory-summary-note">
                        Total dari kolom stok produk.
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Stok FIFO
                    </div>

                    <div class="inventory-summary-value text-success">
                        {{ $totalFifoStockQty }}
                    </div>

                    <div class="inventory-summary-note">
                        Total qty remaining batch FIFO.
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Produk Selisih Batch
                    </div>

                    <div class="inventory-summary-value {{ $stockMismatchCount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $stockMismatchCount }}
                    </div>

                    <div class="inventory-summary-note">
                        Idealnya 0 produk.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($stockMismatchCount > 0)
        <div class="alert alert-warning">
            Ada <strong>{{ $stockMismatchCount }}</strong> produk yang stok sistemnya berbeda dengan total stok batch FIFO.
            Silakan cek halaman
            <a href="{{ route('admin.inventory.batches') }}" class="alert-link">
                Batch FIFO
            </a>.
        </div>
    @endif

    {{-- Pembanding Sistem vs FIFO --}}
    <div class="mb-3">
        <div class="inventory-section-title">
            Pembanding Stok Sistem vs FIFO
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            Perbandingan Qty Stok
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-striped mb-0 inventory-compare-table">
                            <tbody>
                                <tr>
                                    <th>Stok Sistem</th>
                                    <td class="text-end">
                                        {{ $totalSystemStock }} Unit
                                    </td>
                                </tr>

                                <tr>
                                    <th>Stok FIFO</th>
                                    <td class="text-end">
                                        {{ $totalFifoStockQty }} Unit
                                    </td>
                                </tr>

                                <tr class="fw-bold">
                                    <th>Selisih</th>
                                    <td class="text-end {{ $stockDifference != 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $stockDifference }} Unit
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer text-muted">
                        Selisih ideal adalah 0. Jika tidak 0, cek sinkronisasi batch FIFO.
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            Perbandingan Nilai Modal
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-striped mb-0 inventory-compare-table">
                            <tbody>
                                <tr>
                                    <th>Nilai Modal FIFO</th>
                                    <td class="text-end">
                                        Rp {{ number_format($fifoValue, 0, ',', '.') }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Nilai Modal Last Cost</th>
                                    <td class="text-end">
                                        Rp {{ number_format($lastCostValue, 0, ',', '.') }}
                                    </td>
                                </tr>

                                <tr class="fw-bold">
                                    <th>Selisih Nilai</th>
                                    <td class="text-end {{ $valueDifference != 0 ? 'text-warning' : 'text-success' }}">
                                        Rp {{ number_format($valueDifference, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer text-muted">
                        Nilai FIFO adalah nilai stok yang dipakai untuk laporan modal stok.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nilai Stok FIFO --}}
    <div class="mb-3">
        <div class="inventory-section-title">
            Ringkasan Nilai Stok FIFO
        </div>

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Nilai Modal Stok FIFO
                    </div>

                    <div class="inventory-summary-value text-success">
                        Rp {{ number_format($fifoValue, 0, ',', '.') }}
                    </div>

                    <div class="inventory-summary-note">
                        Berdasarkan qty remaining × cost price batch.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Total Qty FIFO
                    </div>

                    <div class="inventory-summary-value text-primary">
                        {{ $totalFifoStockQty }} Unit
                    </div>

                    <div class="inventory-summary-note">
                        Total stok yang masih tersedia di batch FIFO.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="inventory-summary-card p-3">
                    <div class="inventory-summary-label">
                        Selisih Sistem vs FIFO
                    </div>

                    <div class="inventory-summary-value {{ $stockDifference != 0 ? 'text-danger' : 'text-success' }}">
                        {{ $stockDifference }} Unit
                    </div>

                    <div class="inventory-summary-note">
                        Idealnya harus 0 unit.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Stok --}}
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Detail Stok Produk</h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0 inventory-report-table">
                    <thead>
                        <tr>
                            <th class="align-middle">Produk</th>
                            <th class="align-middle">Kategori</th>
                            <th class="text-end align-middle text-nowrap">Modal</th>
                            <th class="text-end align-middle text-nowrap">Harga Jual</th>
                            <th class="text-center align-middle">Stok</th>
                            <th class="text-center align-middle">Minimum</th>
                            <th class="text-center align-middle">Status</th>
                            <th class="text-end align-middle text-nowrap">Nilai Modal FIFO</th>
                            <th class="text-end align-middle text-nowrap">Estimasi Jual</th>
                            <th class="text-end align-middle text-nowrap">Potensi Laba</th>
                            <th class="align-middle">Update Stok</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $fifoStockQty = (int) ($product->fifo_stock_qty ?? 0);
                                $fifoStockValue = (float) ($product->fifo_stock_value ?? 0);
                                $fifoAverageCost = (float) ($product->fifo_average_cost ?? 0);

                                $stockSalesValue = $fifoStockQty * $product->price;
                                $stockProfitValue = $stockSalesValue - $fifoStockValue;

                                $hasStockMismatch = (int) $product->stock !== $fifoStockQty;
                            @endphp

                            <tr>
                                <td class="align-middle product-stock-name">
                                    <strong>{{ $product->name }}</strong>

                                    @if (!$product->is_active)
                                        <span class="badge bg-secondary ms-1">Nonaktif</span>
                                    @endif

                                    <br>

                                    <small class="text-muted">
                                        SKU: {{ $product->sku ?? '-' }}
                                    </small>
                                </td>

                                <td class="align-middle">
                                    {{ $product->category?->name ?? '-' }}
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    <small class="text-muted">Terakhir:</small>
                                    <br>
                                    Rp {{ number_format($product->cost_price, 0, ',', '.') }}

                                    <br>

                                    <small class="text-muted">FIFO Avg:</small>
                                    <br>
                                    Rp {{ number_format($fifoAverageCost, 0, ',', '.') }}
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>

                                <td class="text-center align-middle">
                                    {{ $product->stock }}

                                    <br>

                                    <small class="text-muted">
                                        FIFO: {{ $fifoStockQty }}
                                    </small>

                                    @if ($hasStockMismatch)
                                        <br>
                                        <span class="badge bg-danger">
                                            Selisih
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center align-middle">
                                    {{ $product->minimum_stock }}
                                </td>

                                <td class="text-center align-middle">
                                    @if ($product->stock <= 0)
                                        <span class="badge bg-danger">Habis</span>
                                    @elseif ($product->stock <= $product->minimum_stock)
                                        <span class="badge bg-warning">Menipis</span>
                                    @else
                                        <span class="badge bg-success">Aman</span>
                                    @endif
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($fifoStockValue, 0, ',', '.') }}
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($stockSalesValue, 0, ',', '.') }}
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($stockProfitValue, 0, ',', '.') }}
                                </td>

                                <td class="align-middle update-stock-info">
                                    <small>
                                        {{ $product->lastStockUpdatedBy?->name ?? '-' }}
                                        <br>
                                        {{ $product->last_stock_updated_at?->format('d/m/Y H:i') ?? '-' }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Tidak ada produk sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($products->count() > 0)
                        @php
                            $pageProducts = $products->getCollection();
                        @endphp

                        <tfoot>
                            <tr class="fw-bold align-middle">
                                <th colspan="4" class="text-end align-middle">TOTAL HALAMAN INI</th>

                                <th class="text-center align-middle">
                                    {{ $pageProducts->sum('stock') }}

                                    <br>

                                    <small>
                                        FIFO:
                                        {{ $pageProducts->sum(fn($product) => (int) ($product->fifo_stock_qty ?? 0)) }}
                                    </small>
                                </th>

                                <th colspan="2"></th>

                                <th class="text-end align-middle text-nowrap">
                                    Rp
                                    {{ number_format($pageProducts->sum(fn($product) => (float) ($product->fifo_stock_value ?? 0)), 0, ',', '.') }}
                                </th>

                                <th class="text-end align-middle text-nowrap">
                                    Rp
                                    {{ number_format($pageProducts->sum(fn($product) => ((int) ($product->fifo_stock_qty ?? 0)) * $product->price), 0, ',', '.') }}
                                </th>

                                <th class="text-end align-middle text-nowrap">
                                    Rp
                                    {{ number_format($pageProducts->sum(fn($product) => ((int) ($product->fifo_stock_qty ?? 0)) * $product->price - (float) ($product->fifo_stock_value ?? 0)), 0, ',', '.') }}
                                </th>

                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @if ($products->hasPages())
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        @endif
    </div>

@endsection
