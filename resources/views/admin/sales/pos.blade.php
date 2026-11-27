@extends('layouts.admin')

@section('title', 'Penjualan / Kasir')
@section('page-title', 'Penjualan / Kasir')
@section('page-subtitle', 'Pilih produk, tentukan metode pembayaran, lalu proses transaksi kasir.')
@section('content')

    @php
        $categories = $products->pluck('category')->filter()->unique('id');
    @endphp

    <div class="pos-page">
        {{-- Shift Status Bar / Alert --}}
        @if (! $activeShift)
            <div class="pos-shift-notice" role="alert">
                <div class="pos-shift-meta">
                    <span class="material-symbols-outlined">lock_clock</span>
                    <div class="pos-shift-details">
                        <strong>Shift Kasir Belum Terbuka</strong>
                        <small>Buka shift kasir dan masukkan modal awal di laci sebelum memproses transaksi penjualan.</small>
                    </div>
                </div>
                <div class="pos-shift-actions">
                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#openShiftModal">
                        Buka Shift Sekarang
                    </button>
                </div>
            </div>
        @else
            <div class="pos-shift-bar">
                <div class="pos-shift-meta">
                    <span class="crud-status is-success">
                        <span class="status-dot is-success"></span>
                        Shift Aktif (#{{ $activeShift->id }})
                    </span>
                    <div class="pos-shift-details">
                        <strong>{{ auth()->user()->name }} · Buka sejak {{ $activeShift->opened_at->format('H:i') }}</strong>
                        <small>Modal Awal Laci: Rp {{ number_format((float) $activeShift->starting_cash, 0, ',', '.') }}</small>
                    </div>
                </div>
                <div class="pos-shift-actions">
                    @if (auth()->user()->canViewShiftTotals())
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOpenRunningShiftSummary" data-bs-toggle="modal" data-bs-target="#runningShiftSummaryModal">
                            Ringkasan Shift
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                        Tutup Shift
                    </button>
                </div>
            </div>
        @endif

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

    {{-- Modal Buka Shift --}}
    <div class="modal fade" id="openShiftModal" tabindex="-1" aria-labelledby="openShiftModalLabel" aria-hidden="true" {{ ! $activeShift ? 'data-bs-backdrop="static"' : '' }}>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.shifts.open') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="openShiftModalLabel">Buka Shift Kasir</h5>
                        @if ($activeShift)
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        @endif
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            Masukkan nominal modal awal (starting float / uang kembalian) yang ada di laci kasir saat ini.
                        </p>
                        <div class="mb-3">
                            <label for="starting_cash" class="form-label fw-bold">Modal Awal Kas (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="starting_cash" id="starting_cash" class="form-control form-control-lg"
                                    placeholder="0" min="0" step="1000" value="{{ old('starting_cash', 0) }}" required autofocus>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('starting_cash').value = 0;">Rp 0</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('starting_cash').value = 100000;">Rp 100.000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('starting_cash').value = 200000;">Rp 200.000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('starting_cash').value = 500000;">Rp 500.000</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Buka Shift & Mulai Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($activeShift)
        @if (auth()->user()->canViewShiftTotals())
            {{-- Modal Ringkasan Shift Berjalan (Khusus Admin & Supervisor) --}}
            <div class="modal fade" id="runningShiftSummaryModal" tabindex="-1" aria-labelledby="runningShiftSummaryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="runningShiftSummaryModalLabel">Ringkasan Shift Berjalan (Sementara)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="runningShiftSummaryContent">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted small">Memuat ringkasan real-time shift aktif...</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal Tutup Shift & Rekonsiliasi Kas --}}
        <div class="modal fade" id="closeShiftModal" tabindex="-1" aria-labelledby="closeShiftModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.shifts.close', $activeShift) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="closeShiftModalLabel">Tutup Shift & Rekonsiliasi Kas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info small mb-3">
                                Hitung seluruh uang tunai fisik yang ada di dalam laci kasir (termasuk modal awal), lalu masukkan nominalnya di bawah ini.
                            </div>

                            @if ($errors->has('actual_cash') || $errors->has('supervisor_pin') || $errors->has('closing_notes'))
                                <div class="alert alert-danger small mb-3" role="alert">
                                    <strong>Penutupan shift belum dapat diproses:</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach (['actual_cash', 'supervisor_pin', 'closing_notes'] as $field)
                                            @error($field)
                                                <li>{{ $message }}</li>
                                            @enderror
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="actual_cash" class="form-label fw-bold">Total Uang Fisik di Laci Kas (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="actual_cash" id="actual_cash"
                                        class="form-control form-control-lg @error('actual_cash') is-invalid @enderror"
                                        placeholder="0" min="0" step="1000" value="{{ old('actual_cash') }}" required autofocus>
                                </div>
                                <small class="text-muted">Sistem akan membandingkan uang fisik dengan ekspektasi sistem secara otomatis.</small>
                            </div>

                            <div class="mb-3">
                                <label for="closing_notes" class="form-label">Catatan Penutupan</label>
                                <textarea name="closing_notes" id="closing_notes" rows="3"
                                    class="form-control @error('closing_notes') is-invalid @enderror"
                                    placeholder="Catatan jika ada uang rusak, kelebihan/kekurangan, atau catatan serah terima shift...">{{ old('closing_notes') }}</textarea>
                            </div>

                            @if (! auth()->user()->canAuthorizeCashier())
                                <div class="mb-3">
                                    <label for="close_supervisor_pin" class="form-label fw-bold">
                                        PIN Otorisasi Supervisor
                                        @if ($errors->has('supervisor_pin'))
                                            <span class="text-danger">* (Wajib karena terdapat selisih kas)</span>
                                        @else
                                            <span class="text-muted fw-normal">(Wajib jika terdapat selisih kas)</span>
                                        @endif
                                    </label>
                                    <input type="password" name="supervisor_pin" id="close_supervisor_pin"
                                        class="form-control @error('supervisor_pin') is-invalid @enderror"
                                        placeholder="Kosongkan jika kas sesuai, isi PIN Supervisor jika ada selisih"
                                        maxlength="10" autocomplete="off">
                                    <small class="text-muted">Jika uang fisik berbeda dengan ekspektasi sistem, penutupan shift harus disetujui Supervisor.</small>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger fw-bold" onclick="return confirm('Apakah Anda yakin ingin menutup shift kasir ini? Tindakan ini tidak dapat dibatalkan.')">
                                Tutup Shift & Buat Laporan Rekonsiliasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        window.POS_CONFIG = {
            checkoutUrl: "{{ route('admin.sales.checkout') }}",
            csrfToken: "{{ csrf_token() }}",
            hasActiveShift: {{ $activeShift ? 'true' : 'false' }}
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

            // Shift Modals Integration
            @if (!$activeShift)
                const openShiftModalEl = document.getElementById('openShiftModal');
                if (openShiftModalEl && typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(openShiftModalEl);
                    modal.show();
                }
            @else
                @if ($errors->has('actual_cash') || $errors->has('supervisor_pin') || $errors->has('closing_notes'))
                    const closeShiftModalEl = document.getElementById('closeShiftModal');
                    if (closeShiftModalEl && typeof bootstrap !== 'undefined') {
                        const closeModal = new bootstrap.Modal(closeShiftModalEl);
                        closeModal.show();
                    }
                @endif

                @if (auth()->user()->canViewShiftTotals())
                    const btnOpenRunningShiftSummary = document.getElementById('btnOpenRunningShiftSummary');
                    if (btnOpenRunningShiftSummary) {
                        btnOpenRunningShiftSummary.addEventListener('click', function() {
                            const summaryContent = document.getElementById('runningShiftSummaryContent');
                            if (!summaryContent) return;

                            summaryContent.innerHTML = `
                                <div class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted small">Memuat ringkasan real-time shift aktif...</p>
                                </div>
                            `;

                            fetch("{{ route('admin.shifts.current') }}")
                                .then(res => res.json())
                                .then(data => {
                                    if (data.has_active_shift) {
                                        const s = data.shift;
                                        const formatRp = (num) => 'Rp ' + Number(num).toLocaleString('id-ID');
                                        summaryContent.innerHTML = `
                                            <div class="p-2">
                                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                    <span>Kasir:</span>
                                                    <strong>${s.cashier_name}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Waktu Buka:</span>
                                                    <span>${s.opened_at}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Modal Awal (Float):</span>
                                                    <strong>${formatRp(s.starting_cash)}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Penjualan Tunai Bersih (Cash):</span>
                                                    <span>${formatRp(s.total_cash_sales)}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Penjualan QRIS:</span>
                                                    <span>${formatRp(s.total_qris_sales)}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Penjualan Transfer:</span>
                                                    <span>${formatRp(s.total_transfer_sales)}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 pt-2 border-top fw-bold">
                                                    <span>Total Penjualan Shift:</span>
                                                    <span>${formatRp(s.total_sales)}</span>
                                                </div>
                                                <div class="d-flex justify-content-between pt-2 border-top fw-bold text-success">
                                                    <span>Ekspektasi Uang Tunai di Laci:</span>
                                                    <span class="fs-5">${formatRp(s.expected_cash)}</span>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    * Ekspektasi kas = Modal awal + total penerimaan tunai bersih (setelah dikurangi kembalian).
                                                </small>
                                            </div>
                                        `;
                                    } else {
                                        summaryContent.innerHTML = `<div class="alert alert-warning mb-0">Tidak ada shift yang sedang aktif.</div>`;
                                    }
                                })
                                .catch(() => {
                                    summaryContent.innerHTML = `<div class="alert alert-danger mb-0">Gagal mengambil ringkasan shift berjalan.</div>`;
                                });
                        });
                    }
                @endif
            @endif
        });
    </script>

    @vite(['resources/js/admin/pos.js'])
@endpush
