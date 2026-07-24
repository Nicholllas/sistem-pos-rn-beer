@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')
@section('page-subtitle', 'Kelola identitas, harga, stok minimum, dan status produk.')

@section('page-actions')
    <a href="{{ route('admin.products.trash') }}" class="btn btn-outline-secondary">
        Produk Terhapus
    </a>

    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        Tambah Produk
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Produk</h2>

                    <p>
                        Total {{ number_format($products->total(), 0, ',', '.') }}
                        produk terdaftar.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $products->total() }} produk
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table product-crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Persediaan</th>
                            <th>Status</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $hasHistory = $product->hasTransactionHistory();

                                $grossProfit = max((float) $product->price - (float) $product->cost_price, 0);

                                $grossMargin = $product->price > 0 ? ($grossProfit / $product->price) * 100 : 0;

                                $isLowStock = $product->stock <= $product->minimum_stock;
                            @endphp

                            <tr>
                                <td>
                                    <div class="product-record-cell">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                                class="product-table-image" width="50" height="50">
                                        @else
                                            <span class="product-table-image-placeholder">
                                                {{ strtoupper(substr($product->name, 0, 1)) }}
                                            </span>
                                        @endif

                                        <div class="product-record-info">
                                            <strong>{{ $product->name }}</strong>

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
                                    <div class="product-price-cell">
                                        <strong>
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </strong>

                                        <span>
                                            Modal:
                                            Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                        </span>

                                        <small>
                                            Margin {{ number_format($grossMargin, 1, ',', '.') }}%
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="product-stock-cell">
                                        <span
                                            class="product-stock-badge
                                            {{ $isLowStock ? 'is-danger' : 'is-success' }}">
                                            {{ number_format($product->stock, 0, ',', '.') }}
                                        </span>

                                        <small>
                                            Minimum {{ number_format($product->minimum_stock, 0, ',', '.') }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="crud-status
                                        {{ $product->is_active ? 'is-success' : 'is-muted' }}">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $product->updatedBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            Data:
                                            {{ $product->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>

                                        <small>
                                            Stok:
                                            {{ $product->last_stock_updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        @if (!$hasHistory)
                                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                                class="js-confirm-action" data-confirm-icon="warning"
                                                data-confirm-title="Hapus produk?"
                                                data-confirm-message="Produk {{ $product->name }} akan dipindahkan ke trash dan masih dapat dipulihkan."
                                                data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-light crud-locked-button"
                                                title="Produk memiliki riwayat transaksi" disabled>
                                                Terkunci
                                            </button>
                                        @endif
                                    </div>

                                    @if ($hasHistory)
                                        <small class="crud-lock-note">
                                            Memiliki riwayat transaksi
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            P
                                        </span>

                                        <strong>Belum ada produk</strong>

                                        <p>
                                            Tambahkan produk pertama agar dapat digunakan
                                            pada transaksi penjualan.
                                        </p>

                                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                                            Tambah Produk
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
