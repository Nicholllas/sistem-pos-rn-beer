@extends('layouts.admin')

@section('title', 'Stok Menipis')
@section('page-title', 'Stok Menipis')
@section('page-subtitle', 'Pantau produk yang stoknya sudah mencapai atau berada di bawah batas minimum.')

@section('page-actions')
    <a href="{{ route('admin.inventory.adjustment') }}" class="btn btn-outline-secondary">
        Penyesuaian Stok
    </a>

    <a href="{{ route('admin.inventory.stock-in') }}" class="btn btn-primary">
        Stok Masuk
    </a>
@endsection

@section('content')
    <div class="crud-page low-stock-page">
        <div class="low-stock-notice">
            <span class="low-stock-notice-icon">
                !
            </span>

            <div>
                <strong>Produk berikut memerlukan perhatian</strong>

                <p>
                    Stok produk sudah mencapai atau berada di bawah batas minimum.
                    Segera lakukan pembelian, stok masuk, atau pemeriksaan fisik.
                </p>
            </div>

            <span class="low-stock-notice-count">
                {{ number_format($products->total(), 0, ',', '.') }}
                produk
            </span>
        </div>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Stok Menipis</h2>

                    <p>
                        Produk diurutkan berdasarkan kondisi stok yang memerlukan
                        tindak lanjut.
                    </p>
                </div>

                <span class="crud-count-badge is-warning">
                    {{ $products->total() }} perlu diperiksa
                </span>
            </div>

            <div class="low-stock-summary">
                <div>
                    <span>Total Produk Menipis</span>

                    <strong>
                        {{ number_format($products->total(), 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Ditampilkan</span>

                    <strong>
                        {{ number_format($products->count(), 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Halaman</span>

                    <strong>
                        {{ $products->currentPage() }}
                        dari
                        {{ $products->lastPage() }}
                    </strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table crud-table low-stock-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Stok Saat Ini</th>
                            <th>Batas Minimum</th>
                            <th>Kondisi</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $currentStock = (int) $product->stock;
                                $minimumStock = (int) $product->minimum_stock;

                                $isOutOfStock = $currentStock <= 0;

                                $stockGap = max($minimumStock - $currentStock, 0);

                                $stockPercentage =
                                    $minimumStock > 0
                                        ? min(100, max(0, round(($currentStock / $minimumStock) * 100)))
                                        : 0;
                            @endphp

                            <tr @class([
                                'is-out-of-stock' => $isOutOfStock,
                            ])>
                                <td>
                                    <div class="product-record-cell">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                                class="product-table-image" width="50" height="50">
                                        @else
                                            <span class="product-table-image-placeholder">
                                                {{ strtoupper(mb_substr($product->name, 0, 1)) }}
                                            </span>
                                        @endif

                                        <div class="product-record-info">
                                            <strong>
                                                {{ $product->name }}
                                            </strong>

                                            <span>
                                                SKU {{ $product->sku }}
                                            </span>

                                            <small>
                                                Barcode:
                                                {{ $product->barcode ?: '-' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="crud-neutral-badge">
                                        {{ $product->category?->name ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="low-stock-current">
                                        <strong class="{{ $isOutOfStock ? 'is-danger' : 'is-warning' }}">
                                            {{ number_format($currentStock, 0, ',', '.') }}
                                        </strong>

                                        <span>
                                            unit tersedia
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="low-stock-minimum">
                                        <strong>
                                            {{ number_format($minimumStock, 0, ',', '.') }}
                                        </strong>

                                        <span>
                                            batas peringatan
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="low-stock-condition">
                                        @if ($isOutOfStock)
                                            <span class="crud-status is-danger">
                                                Stok Habis
                                            </span>
                                        @else
                                            <span class="crud-status is-warning">
                                                Stok Menipis
                                            </span>
                                        @endif

                                        <div class="low-stock-gap">
                                            Kurang
                                            <strong>
                                                {{ number_format($stockGap, 0, ',', '.') }}
                                            </strong>
                                            untuk mencapai minimum
                                        </div>

                                        <div class="low-stock-progress" role="progressbar"
                                            aria-label="Perbandingan stok dengan minimum"
                                            aria-valuenow="{{ $stockPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                            <span class="{{ $isOutOfStock ? 'is-danger' : 'is-warning' }}"
                                                style="width: {{ $stockPercentage }}%"></span>
                                        </div>
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

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Lihat Produk
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar is-success">
                                            ✓
                                        </span>

                                        <strong>Semua stok dalam kondisi aman</strong>

                                        <p>
                                            Tidak ada produk yang stoknya mencapai
                                            atau berada di bawah batas minimum.
                                        </p>

                                        <a href="{{ route('admin.products.index') }}"
                                            class="btn btn-outline-primary btn-sm">
                                            Lihat Semua Produk
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $products->firstItem() }}
                        sampai {{ $products->lastItem() }}
                        dari {{ $products->total() }} produk
                    </span>

                    {{ $products->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
