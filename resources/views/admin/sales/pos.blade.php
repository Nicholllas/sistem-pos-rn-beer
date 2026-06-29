@extends('layouts.admin')

@section('title', 'Penjualan / Kasir')
@section('page-title', 'Penjualan / Kasir')

@section('content')

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Daftar Produk</h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" id="productSearch" class="form-control"
                            placeholder="Cari produk berdasarkan nama, SKU, atau barcode...">
                    </div>

                    <div class="row" id="productList">
                        @forelse ($products as $product)
                            <div class="col-md-6 mb-3 product-card" data-name="{{ $product->name }}"
                                data-sku="{{ $product->sku }}" data-barcode="{{ $product->barcode }}">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="mb-2 fw-semibold product-pos-name">
                                            {{ $product->name }}
                                        </h5>

                                        <div class="product-pos-meta">
                                            <small class="text-muted d-block">
                                                SKU: {{ $product->sku }}
                                            </small>

                                            <small class="text-muted d-block">
                                                Barcode: {{ $product->barcode ?? '-' }}
                                            </small>

                                            <small class="text-muted d-block">
                                                Stok: {{ $product->stock }}
                                            </small>
                                        </div>

                                        <div class="mt-2">
                                            <strong>
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <button type="button" class="btn btn-primary btn-sm mt-2 add-to-cart"
                                            data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                            data-price="{{ $product->price }}" data-stock="{{ $product->stock }}">
                                            Tambah
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    Tidak ada produk aktif dengan stok tersedia.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Keranjang</h3>
                </div>

                <form action="{{ route('admin.sales.checkout') }}" method="POST" id="checkoutForm">
                    @csrf

                    <div class="card-body">
                        <div id="cartItems">
                            <p class="text-muted text-center mb-0">
                                Keranjang masih kosong.
                            </p>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong id="grandTotalText">Rp 0</strong>
                        </div>

                        <div id="cartHiddenInputs"></div>
                        <div id="paymentHiddenInputs"></div>

                        <div class="mt-3">
                            <label class="form-label">
                                Pembayaran
                            </label>

                            <div class="row">
                                <div class="col-12 col-sm-4 mb-2">
                                    <label for="cash_amount" class="form-label small mb-1">
                                        Cash
                                    </label>

                                    <input type="number" id="cash_amount" class="form-control payment-amount"
                                        data-method="cash" min="0" step="1" value="0">
                                </div>

                                <div class="col-12 col-sm-4 mb-2">
                                    <label for="qris_amount" class="form-label small mb-1">
                                        QRIS
                                    </label>

                                    <input type="number" id="qris_amount" class="form-control payment-amount"
                                        data-method="qris" min="0" step="1" value="0">
                                </div>

                                <div class="col-12 col-sm-4 mb-2">
                                    <label for="transfer_amount" class="form-label small mb-1">
                                        Transfer
                                    </label>

                                    <input type="number" id="transfer_amount" class="form-control payment-amount"
                                        data-method="transfer" min="0" step="1" value="0">
                                </div>
                            </div>

                            <small id="paymentHint" class="text-muted d-block mt-1">
                                Masukkan nominal pembayaran.
                            </small>
                        </div>

                        <div class="mt-3 d-flex justify-content-between">
                            <span>Kembalian</span>
                            <strong id="changeText">Rp 0</strong>
                        </div>

                        @error('items')
                            <div class="alert alert-danger mt-3">
                                {{ $message }}
                            </div>
                        @enderror

                        @error('payments')
                            <div class="alert alert-danger mt-3">
                                {{ $message }}
                            </div>
                        @enderror

                        @error('payments.*.payment_method')
                            <div class="alert alert-danger mt-3">
                                {{ $message }}
                            </div>
                        @enderror

                        @error('payments.*.amount')
                            <div class="alert alert-danger mt-3">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="card-footer">
                        <button type="submit" id="checkoutButton" class="btn btn-success w-100">
                            Checkout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.POS_CONFIG = {
            checkoutUrl: "{{ route('admin.sales.checkout') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>

    @vite(['resources/js/admin/pos.js'])
@endpush
