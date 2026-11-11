@extends('layouts.admin')

@section('title', 'Penjualan / Kasir')
@section('page-title', 'Penjualan / Kasir')
@section('page-subtitle', 'Pilih produk, tentukan metode pembayaran, lalu proses transaksi kasir.')
@section('content')

    @php
        $categories = $products->pluck('category')->filter()->unique('id');
    @endphp

    <div class="pos-page">
        <div class="row g-3">

            {{-- =====================================================
                 PRODUCT SECTION (Clean White Catalog Surface)
            ====================================================== --}}
            <div class="col-lg-7">
                <div class="card pos-products-card">
                    <div class="card-header pos-card-header">
                        <div class="pos-card-heading">
                            <h3 class="card-title">Katalog Produk</h3>
                            <span class="pos-badge-count">
                                {{ $products->count() }} item tersedia
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3">
                        {{-- Search Input with Shortcut Hint --}}
                        <div class="pos-search-wrapper">
                            <span class="pos-search-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg>
                            </span>

                            <input type="text" id="productSearch" class="form-control pos-search-input"
                                placeholder="Cari nama produk, SKU, atau barcode... (Tekan '/' untuk fokus)"
                                autocomplete="off" autofocus>
                        </div>

                        {{-- Category Filter Pills (Rapid Cashier Filtering) --}}
                        @if ($categories->isNotEmpty())
                            <div class="pos-category-filters" id="categoryFilters" role="tablist">
                                <button type="button" class="pos-filter-pill is-active" data-category="all">
                                    Semua Kategori
                                </button>
                                @foreach ($categories as $category)
                                    <button type="button" class="pos-filter-pill" data-category="{{ $category->name }}">
                                        {{ $category->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Compact Product Grid --}}
                        <div class="row g-2" id="productList">
                            @forelse ($products as $product)
                                <div class="col-sm-6 col-md-4 product-card"
                                    data-name="{{ $product->name }}"
                                    data-sku="{{ $product->sku }}"
                                    data-barcode="{{ $product->barcode }}"
                                    data-category="{{ $product->category->name ?? '' }}">
                                    <div class="card pos-product-card">
                                        <div class="card-body">
                                            <div class="pos-product-top">
                                                <div class="pos-product-content">
                                                    <h5 class="product-pos-name" title="{{ $product->name }}">
                                                        {{ $product->name }}
                                                    </h5>

                                                    @if ($product->category)
                                                        <span class="pos-category-badge">
                                                            {{ $product->category->name }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <span class="pos-stock-badge {{ $product->stock <= $product->minimum_stock ? 'pos-stock-low' : 'pos-stock-available' }}">
                                                    Stok {{ $product->stock }}
                                                </span>
                                            </div>

                                            <div class="product-pos-meta">
                                                <span>SKU: <strong>{{ $product->sku }}</strong></span>
                                                @if ($product->barcode)
                                                    <span>{{ $product->barcode }}</span>
                                                @endif
                                            </div>

                                            <div class="pos-product-footer">
                                                <div class="pos-product-price">
                                                    <small>Harga</small>
                                                    <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                                </div>

                                                <button type="button" class="add-to-cart"
                                                    data-id="{{ $product->id }}"
                                                    data-name="{{ $product->name }}"
                                                    data-price="{{ $product->price }}"
                                                    data-stock="{{ $product->stock }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <line x1="12" y1="5" x2="12" y2="19" />
                                                        <line x1="5" y1="12" x2="19" y2="12" />
                                                    </svg>
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
                                Coba gunakan kata kunci nama, SKU, atau barcode yang berbeda.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =====================================================
                 CART & CHECKOUT SECTION (Sticky Ergonomic Drawer)
            ====================================================== --}}
            <div class="col-lg-5">
                <div class="card pos-cart-card">
                    <div class="card-header pos-card-header">
                        <div class="pos-card-heading">
                            <h3 class="card-title">Keranjang Belanja</h3>
                            <span class="pos-badge-count" id="cartItemBadge">
                                Transaksi Baru
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('admin.sales.checkout') }}" method="POST" id="checkoutForm">
                        @csrf

                        <div class="card-body p-3">
                            {{-- Cart Items --}}
                            <div class="pos-cart-section">
                                <div id="cartItems">
                                    <div class="pos-cart-empty">
                                        <div class="pos-cart-empty-title">
                                            Keranjang masih kosong
                                        </div>
                                        <p>
                                            Pilih produk dari katalog untuk memulai transaksi.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Grand Total Section (Prominent Trustworthy Blue Accent) --}}
                            <div class="pos-total-section">
                                <span>Total Belanja</span>
                                <strong id="grandTotalText">
                                    Rp 0
                                </strong>
                            </div>

                            <div id="cartHiddenInputs"></div>
                            <div id="paymentHiddenInputs"></div>

                            {{-- Payment Methods --}}
                            <div class="pos-payment-section">
                                <div class="pos-section-heading">
                                    <h4>Metode Pembayaran</h4>
                                    <small>Mendukung pembayaran tunggal maupun gabungan (split bill).</small>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-4 col-sm-12">
                                        <label for="cash_amount" class="payment-method-box">
                                            <span class="payment-method-name">Cash</span>
                                            <span class="payment-method-description">Tunai</span>

                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" id="cash_amount" class="form-control payment-amount"
                                                    data-method="cash" min="0" step="1" value="0"
                                                    inputmode="numeric">
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <label for="qris_amount" class="payment-method-box">
                                            <span class="payment-method-name">QRIS</span>
                                            <span class="payment-method-description">Non-Tunai</span>

                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" id="qris_amount" class="form-control payment-amount"
                                                    data-method="qris" min="0" step="1" value="0"
                                                    inputmode="numeric">
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <label for="transfer_amount" class="payment-method-box">
                                            <span class="payment-method-name">Transfer</span>
                                            <span class="payment-method-description">Bank</span>

                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" id="transfer_amount"
                                                    class="form-control payment-amount" data-method="transfer"
                                                    min="0" step="1" value="0" inputmode="numeric">
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- Quick Cash Presets --}}
                                <div class="pos-quick-cash">
                                    <span class="pos-quick-cash-label">Cepat:</span>
                                    <button type="button" class="pos-quick-cash-btn" data-preset="pas">
                                        Uang Pas
                                    </button>
                                    <button type="button" class="pos-quick-cash-btn" data-preset="50000">
                                        50k
                                    </button>
                                    <button type="button" class="pos-quick-cash-btn" data-preset="100000">
                                        100k
                                    </button>
                                    <button type="button" class="pos-quick-cash-btn" data-preset="200000">
                                        200k
                                    </button>
                                </div>

                                <div id="paymentHint" class="pos-payment-hint is-neutral">
                                    Tambahkan produk ke keranjang terlebih dahulu.
                                </div>
                            </div>

                            {{-- Change / Kembalian --}}
                            <div class="pos-change-section">
                                <div>
                                    <span>Kembalian</span>
                                    <small>Pastikan diserahkan kepada pelanggan.</small>
                                </div>

                                <strong id="changeText">
                                    Rp 0
                                </strong>
                            </div>

                            {{-- Validation Errors --}}
                            @if (isset($errors) && $errors->any())
                                <div class="alert alert-danger pos-validation-alert" role="alert">
                                    <strong>Transaksi belum dapat diproses:</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        {{-- Primary CTA Footer --}}
                        <div class="card-footer pos-checkout-footer">
                            <button type="submit" id="checkoutButton" class="pos-checkout-button" disabled>
                                Keranjang Kosong
                            </button>

                            <small>
                                Periksa kembali rincian produk dan nominal pembayaran sebelum checkout.
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

        // Keyboard Shortcut: '/' to quickly focus the product search input
        document.addEventListener('keydown', function(event) {
            if (event.key === '/' && document.activeElement.tagName !== 'INPUT') {
                event.preventDefault();
                const searchInput = document.getElementById('productSearch');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // Category Filter Pills Integration
        document.addEventListener('DOMContentLoaded', function() {
            const categoryPills = document.querySelectorAll('.pos-filter-pill');
            const productCards = document.querySelectorAll('.product-card');

            categoryPills.forEach(pill => {
                pill.addEventListener('click', function() {
                    categoryPills.forEach(p => p.classList.remove('is-active'));
                    this.classList.add('is-active');

                    const selectedCategory = this.dataset.category;

                    productCards.forEach(card => {
                        const cardCategory = card.dataset.category || '';
                        if (selectedCategory === 'all' || cardCategory === selectedCategory) {
                            card.classList.remove('d-none');
                        } else {
                            card.classList.add('d-none');
                        }
                    });
                });
            });

            // Quick Cash Presets Integration
            const quickCashBtns = document.querySelectorAll('.pos-quick-cash-btn');
            const cashInput = document.getElementById('cash_amount');
            const grandTotalText = document.getElementById('grandTotalText');

            quickCashBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!cashInput) return;

                    const preset = this.dataset.preset;
                    let targetAmount = 0;

                    if (preset === 'pas') {
                        // Extract number from grandTotalText e.g. "Rp 75.000" -> 75000
                        const rawText = grandTotalText ? grandTotalText.textContent : '0';
                        const numbersOnly = rawText.replace(/[^0-9]/g, '');
                        targetAmount = parseInt(numbersOnly, 10) || 0;
                    } else {
                        targetAmount = parseInt(preset, 10) || 0;
                    }

                    cashInput.value = targetAmount;
                    cashInput.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        });
    </script>

    @vite(['resources/js/admin/pos.js'])
@endpush
