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
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="stock_status" class="form-label">
                            Status Stok
                        </label>

                        <select name="stock_status" id="stock_status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="safe" @selected(request('stock_status') === 'safe')>
                                Aman
                            </option>
                            <option value="low" @selected(request('stock_status') === 'low')>
                                Menipis
                            </option>
                            <option value="out" @selected(request('stock_status') === 'out')>
                                Habis
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

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $totalProducts }}</h3>
                    <p>Total Produk Aktif</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>{{ $totalStockUnits }}</h3>
                    <p>Total Unit Stok</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $lowStockProducts }}</h3>
                    <p>Produk Menipis</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>{{ $outOfStockProducts }}</h3>
                    <p>Produk Habis</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="card">
                <div class="card-body">
                    <strong>Nilai Modal Stok</strong>
                    <h4 class="mb-0">
                        Rp {{ number_format($totalStockCostValue, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="card">
                <div class="card-body">
                    <strong>Estimasi Nilai Jual Stok</strong>
                    <h4 class="mb-0">
                        Rp {{ number_format($totalStockSalesValue, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="card">
                <div class="card-body">
                    <strong>Potensi Laba Kotor</strong>
                    <h4 class="mb-0">
                        Rp {{ number_format($potentialGrossProfit, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

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
                            <th class="text-end align-middle text-nowrap">Harga Modal</th>
                            <th class="text-end align-middle text-nowrap">Harga Jual</th>
                            <th class="text-center align-middle">Stok</th>
                            <th class="text-center align-middle">Minimum</th>
                            <th class="text-center align-middle">Status</th>
                            <th class="text-end align-middle text-nowrap">Nilai Modal</th>
                            <th class="text-end align-middle text-nowrap">Estimasi Jual</th>
                            <th class="text-end align-middle text-nowrap">Potensi Laba</th>
                            <th class="align-middle">Update Stok</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $stockCostValue = $product->stock * $product->cost_price;
                                $stockSalesValue = $product->stock * $product->price;
                                $stockProfitValue = $stockSalesValue - $stockCostValue;
                            @endphp

                            <tr>
                                <td class="align-middle product-stock-name">
                                    <strong>{{ $product->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        SKU: {{ $product->sku }}
                                    </small>
                                </td>

                                <td class="align-middle">
                                    {{ $product->category?->name ?? '-' }}
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>

                                <td class="text-center align-middle">
                                    {{ $product->stock }}
                                </td>

                                <td class="text-center align-middle">
                                    {{ $product->minimum_stock }}
                                </td>

                                <td class="text-center align-middle">
                                    @if ($product->stock == 0)
                                        <span class="badge bg-danger">Habis</span>
                                    @elseif ($product->stock <= $product->minimum_stock)
                                        <span class="badge bg-warning">Menipis</span>
                                    @else
                                        <span class="badge bg-success">Aman</span>
                                    @endif
                                </td>

                                <td class="text-end align-middle text-nowrap">
                                    Rp {{ number_format($stockCostValue, 0, ',', '.') }}
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
                        <tfoot>
                            <tr class="fw-bold align-middle">
                                <th colspan="4" class="text-end align-middle">TOTAL</th>

                                <th class="text-center align-middle">
                                    {{ $products->sum('stock') }}
                                </th>

                                <th colspan="2"></th>

                                <th class="text-end align-middle text-nowrap">
                                    Rp
                                    {{ number_format($products->sum(fn($product) => $product->stock * $product->cost_price), 0, ',', '.') }}
                                </th>

                                <th class="text-end align-middle text-nowrap">
                                    Rp
                                    {{ number_format($products->sum(fn($product) => $product->stock * $product->price), 0, ',', '.') }}
                                </th>

                                <th class="text-end align-middle text-nowrap">
                                    Rp
                                    {{ number_format($products->sum(fn($product) => $product->stock * $product->price - $product->stock * $product->cost_price), 0, ',', '.') }}
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
