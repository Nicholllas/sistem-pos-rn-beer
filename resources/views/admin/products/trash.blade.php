@extends('layouts.admin')

@section('title', 'Produk Terhapus')
@section('page-title', 'Produk Terhapus')
@section('page-subtitle', 'Pulihkan produk yang sebelumnya telah dihapus.')

@section('page-actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        Kembali ke Produk
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="crud-notice">
            <span class="crud-notice-avatar">
                !
            </span>

            <div>
                <strong>Produk belum terhapus permanen</strong>

                <p>
                    Produk pada halaman ini masih dapat dipulihkan ke daftar aktif.
                </p>
            </div>
        </div>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Produk Terhapus</h2>

                    <p>
                        Total {{ number_format($products->total(), 0, ',', '.') }}
                        produk berada di trash.
                    </p>
                </div>

                <span class="crud-count-badge is-danger">
                    {{ $products->total() }} terhapus
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table product-crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Terakhir Diubah</th>
                            <th>Dihapus Pada</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <div class="product-record-cell">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                                class="product-table-image is-trash" width="50" height="50">
                                        @else
                                            <span class="product-table-image-placeholder is-trash">
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
                                    <strong>
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    {{ number_format($product->stock, 0, ',', '.') }}
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $product->updatedBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $product->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="crud-date-badge is-danger">
                                        {{ $product->deleted_at?->format('d M Y H:i') ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <form action="{{ route('admin.products.restore', $product->id) }}" method="POST"
                                            class="js-confirm-action" data-confirm-icon="question"
                                            data-confirm-title="Pulihkan produk?"
                                            data-confirm-message="Produk {{ $product->name }} akan dikembalikan ke daftar produk aktif."
                                            data-confirm-button="Ya, Pulihkan" data-confirm-class="btn btn-success px-4">
                                            @csrf
                                            @method('PUT')

                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                Pulihkan
                                            </button>
                                        </form>
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

                                        <strong>Trash produk kosong</strong>

                                        <p>
                                            Tidak ada produk yang sedang menunggu
                                            untuk dipulihkan.
                                        </p>

                                        <a href="{{ route('admin.products.index') }}"
                                            class="btn btn-outline-primary btn-sm">
                                            Kembali ke Produk
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
