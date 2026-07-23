@extends('layouts.admin')

@section('title', 'Trash Products')
@section('page-title', 'Trash Products')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                Deleted Products
            </h3>

            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                Back to Products
            </a>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>SKU / Barcode</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Created By</th>
                            <th>Updated By</th>
                            <th>Deleted At</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($products as $product)
                            <tr>
                                <td>
                                    {{ $product->id }}
                                </td>

                                <td>
                                    <strong>{{ $product->name }}</strong>

                                    <div class="text-muted small">
                                        {{ $product->brand }}
                                        {{ $product->variant }}
                                        {{ $product->size }}
                                        {{ $product->unit }}
                                    </div>
                                </td>

                                <td>
                                    {{ $product->category?->name ?? '-' }}
                                </td>

                                <td>
                                    <div>
                                        <strong>SKU:</strong>
                                        {{ $product->sku }}
                                    </div>

                                    <div class="text-muted small">
                                        <strong>Barcode:</strong>
                                        {{ $product->barcode ?? '-' }}
                                    </div>
                                </td>

                                <td>
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($product->stock, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ $product->createdBy?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $product->updatedBy?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $product->deleted_at?->format('d M Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    <form action="{{ route('admin.products.restore', $product->id) }}" method="POST"
                                        onsubmit="return confirm('Restore produk ini?')">

                                        @csrf
                                        @method('PUT')

                                        <button type="submit" class="btn btn-success btn-sm">
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    Trash produk kosong
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>
            </div>

            @if ($products->hasPages())
                <div class="mt-3">
                    {{ $products->links() }}
                </div>
            @endif

        </div>

    </div>

@endsection
