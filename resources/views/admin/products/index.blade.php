@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')

@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                Daftar Produk
            </h3>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.trash') }}" class="btn btn-secondary btn-sm">
                    Trash
                </a>

                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                    Tambah Produk
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 product-table">

                    <thead class="table-light">
                        <tr>
                            <th style="width: 300px;">Produk</th>
                            <th style="min-width: 110px;">Kategori</th>
                            <th style="min-width: 130px;">Harga Modal</th>
                            <th style="min-width: 130px;">Harga Jual</th>
                            <th style="min-width: 90px;">Stok</th>
                            <th style="min-width: 90px;">Status</th>
                            <th style="min-width: 210px;">Info Update</th>
                            <th style="min-width: 90px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                                class="rounded border"
                                                style="width: 56px; height: 56px; object-fit: cover;">
                                        @else
                                            <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted product-image-placeholder"
                                                style="width: 56px; height: 56px; font-size: 11px;">
                                                No Image
                                            </div>
                                        @endif

                                        <div class="product-name">
                                            <strong class="d-block">
                                                {{ $product->name }}
                                            </strong>

                                            <small class="text-muted d-block">
                                                Brand: {{ $product->brand ?? '-' }}
                                            </small>

                                            <small class="text-muted d-block">
                                                Variant: {{ $product->variant ?? '-' }}
                                            </small>

                                            <small class="text-muted d-block">
                                                Ukuran: {{ $product->size ?? '-' }} {{ $product->unit ?? '' }}
                                            </small>

                                            <small class="text-muted d-block">
                                                SKU: {{ $product->sku }}
                                            </small>

                                            <small class="text-muted d-block">
                                                Barcode: {{ $product->barcode ?? '-' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $product->category?->name ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    <strong>
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    @if ($product->stock <= $product->minimum_stock)
                                        <span class="badge bg-danger">
                                            {{ $product->stock }}
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            {{ $product->stock }}
                                        </span>
                                    @endif

                                    <small class="text-muted d-block">
                                        Min: {{ $product->minimum_stock }}
                                    </small>
                                </td>

                                <td>
                                    @if ($product->is_active)
                                        <span class="badge bg-success">
                                            Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <small class="d-block">
                                        <span class="text-muted">Data:</span>
                                        <strong>{{ $product->updatedBy?->name ?? '-' }}</strong>
                                    </small>

                                    <small class="text-muted d-block">
                                        {{ $product->updated_at?->format('d M Y H:i') ?? '-' }}
                                    </small>

                                    <small class="d-block mt-1">
                                        <span class="text-muted">Stok:</span>
                                        <strong>{{ $product->lastStockUpdatedBy?->name ?? '-' }}</strong>
                                    </small>

                                    <small class="text-muted d-block">
                                        {{ $product->last_stock_updated_at?->format('d M Y H:i') ?? '-' }}
                                    </small>
                                </td>

                                <td>
                                    <div class="d-grid gap-1">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        @if (!$product->hasTransactionHistory())
                                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Yakin hapus produk ini?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-danger btn-sm" disabled>
                                                Hapus
                                            </button>
                                            <small class="text-muted d-block" style="line-height: 1.15;">
                                                Ada riwayat transaksi.
                                            </small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada data produk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

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
