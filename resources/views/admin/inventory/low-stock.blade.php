@extends('layouts.admin')

@section('title', 'Stok Menipis')
@section('page-title', 'Stok Menipis')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Daftar Stok Menipis</h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Minimum</th>
                            <th>Terakhir Update Stok</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    <small class="text-muted d-block">
                                        SKU: {{ $product->sku }}
                                    </small>
                                </td>

                                <td>{{ $product->category?->name ?? '-' }}</td>

                                <td>
                                    <span class="badge bg-danger">
                                        {{ $product->stock }}
                                    </span>
                                </td>

                                <td>{{ $product->minimum_stock }}</td>

                                <td>
                                    {{ $product->lastStockUpdatedBy?->name ?? '-' }}
                                    <small class="text-muted d-block">
                                        {{ $product->last_stock_updated_at?->format('d M Y H:i') ?? '-' }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Tidak ada produk stok menipis.
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
