@extends('layouts.admin')

@section('title', 'Penjualan / Kasir')
@section('page-title', 'Penjualan / Kasir')

@push('styles')
    @vite(['resources/css/pos.css'])
@endpush

@section('content')

    <div class="pos-page">
        <div class="row g-4">

            {{-- =====================================================
                 PRODUCT SECTION
            ====================================================== --}}
            <div class="col-lg-7">
                <div class="card pos-products-card">
                    <div class="card-header pos-card-header">
                        <div class="pos-card-heading">
                            <h3 class="card-title">Daftar Produk</h3>

                            <small>
                                {{ $products->count() }} produk tersedia
                            </small>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Search --}}
                        <div class="pos-search-wrapper">
                            <span class="pos-search-label">
                                Cari
                            </span>

                            <input type="text" id="productSearch" class="form-control pos-search-input"
                                placeholder="Nama produk, SKU, atau barcode..." autocomplete="off" autofocus>
                        </div>

                        {{-- Product Grid --}}
                        <div class="row g-3" id="productList">
                            @forelse ($products as $product)
                                <div class="col-sm-6 product-card" data-name="{{ $product->name }}"
                                    data-sku="{{ $product->sku }}" data-barcode="{{ $product->barcode }}">
                                    <div class="card pos-product-card h-100">
                                        <div class="card-body">
                                            <div class="pos-product-top">
                                                <div class="pos-product-content">
                                                    <h5 class="product-pos-name">
                                                        {{ $product->name }}
                                                    </h5>

                                                    @if ($product->category)
                                                        <span class="pos-category-badge">
                                                            {{ $product->category->name }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <span
                                                    class="pos-stock-badge
                                                        {{ $product->stock <= $product->minimum_stock ? 'pos-stock-low' : 'pos-stock-available' }}">
                                                    Stok {{ $product->stock }}
                                                </span>
                                            </div>

                                            <div class="product-pos-meta">
                                                <div>
                                                    <span>SKU</span>
                                                    <strong>{{ $product->sku }}</strong>
                                                </div>

                                                <div>
                                                    <span>Barcode</span>
                                                    <strong>{{ $product->barcode ?? '-' }}</strong>
                                                </div>
                                            </div>

                                            <div class="pos-product-footer">
                                                <div class="pos-product-price">
                                                    <small>Harga jual</small>

                                                    <strong>
                                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                                    </strong>
                                                </div>

                                                <button type="button" class="btn btn-primary btn-sm add-to-cart"
                                                    data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                    data-price="{{ $product->price }}" data-stock="{{ $product->stock }}">
                                                    Tambah
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="pos-empty-state">
                                        <div class="pos-empty-state-title">
                                            Produk belum tersedia
                                        </div>

                                        <p>
                                            Tidak ada produk aktif dengan stok yang dapat dijual.
                                        </p>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        {{-- Search Empty State --}}
                        <div id="productSearchEmpty" class="pos-empty-state d-none">
                            <div class="pos-empty-state-title">
                                Produk tidak ditemukan
                            </div>

                            <p>
                                Coba gunakan nama, SKU, atau barcode yang berbeda.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =====================================================
                 CART SECTION
            ====================================================== --}}
            <div class="col-lg-5">
                <div class="card pos-cart-card">
                    <div class="card-header pos-card-header">
                        <div class="pos-card-heading">
                            <h3 class="card-title">Keranjang</h3>

                            <small>
                                Periksa item sebelum checkout
                            </small>
                        </div>
                    </div>

                    <form action="{{ route('admin.sales.checkout') }}" method="POST" id="checkoutForm">
                        @csrf

                        <div class="card-body">
                            {{-- Cart Items --}}
                            <div class="pos-cart-section">
                                <div id="cartItems">
                                    <div class="pos-cart-empty">
                                        <div class="pos-cart-empty-title">
                                            Keranjang masih kosong
                                        </div>

                                        <p>
                                            Pilih produk dari daftar untuk memulai transaksi.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="pos-total-section">
                                <span>Total Belanja</span>

                                <strong id="grandTotalText">
                                    Rp 0
                                </strong>
                            </div>

                            <div id="cartHiddenInputs"></div>
                            <div id="paymentHiddenInputs"></div>

                            {{-- Payment --}}
                            <div class="pos-payment-section">
                                <div class="pos-section-heading">
                                    <div>
                                        <h4>Pembayaran</h4>

                                        <small>
                                            Bisa menggunakan satu atau beberapa metode.
                                        </small>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-4 col-sm-12">
                                        <label for="cash_amount" class="payment-method-box">
                                            <span class="payment-method-name">
                                                Cash
                                            </span>

                                            <span class="payment-method-description">
                                                Tunai
                                            </span>

                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    Rp
                                                </span>

                                                <input type="number" id="cash_amount" class="form-control payment-amount"
                                                    data-method="cash" min="0" step="1" value="0"
                                                    inputmode="numeric">
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <label for="qris_amount" class="payment-method-box">
                                            <span class="payment-method-name">
                                                QRIS
                                            </span>

                                            <span class="payment-method-description">
                                                Non-tunai
                                            </span>

                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    Rp
                                                </span>

                                                <input type="number" id="qris_amount" class="form-control payment-amount"
                                                    data-method="qris" min="0" step="1" value="0"
                                                    inputmode="numeric">
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <label for="transfer_amount" class="payment-method-box">
                                            <span class="payment-method-name">
                                                Transfer
                                            </span>

                                            <span class="payment-method-description">
                                                Bank
                                            </span>

                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    Rp
                                                </span>

                                                <input type="number" id="transfer_amount"
                                                    class="form-control payment-amount" data-method="transfer"
                                                    min="0" step="1" value="0" inputmode="numeric">
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div id="paymentHint" class="pos-payment-hint">
                                    Masukkan nominal pembayaran sesuai metode yang digunakan.
                                </div>
                            </div>

                            {{-- Change --}}
                            <div class="pos-change-section">
                                <div>
                                    <span>Kembalian</span>

                                    <small>
                                        Pastikan diberikan kepada pelanggan.
                                    </small>
                                </div>

                                <strong id="changeText">
                                    Rp 0
                                </strong>
                            </div>

                            {{-- Validation Errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger pos-validation-alert">
                                    <strong>Transaksi belum dapat diproses.</strong>

                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="card-footer pos-checkout-footer">
                            <button type="submit" id="checkoutButton" class="btn btn-success w-100 pos-checkout-button">
                                Proses Checkout
                            </button>

                            <small>
                                Pastikan item dan nominal pembayaran sudah benar.
                            </small>
                        </div>
                    </form>
                </div>
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
