@extends('layouts.admin')

@section('title', 'Dashboard Penjualan')
@section('page-title', 'Dashboard Penjualan')
@section('page-subtitle', 'Pantau penjualan, pembayaran, persediaan, dan aktivitas toko hari ini.')

@section('content')
    @php
        $authUser = auth()->user();
        $isAdmin = $authUser->isAdmin();

        $paymentSummaryData = collect($paymentSummary ?? []);
        $splitBreakdown = $splitPaymentBreakdown ?? collect();
    @endphp

    <div class="dashboard-page">

        {{-- Welcome --}}
        <section class="dashboard-welcome">
            <div>
                <span class="dashboard-eyebrow">
                    Ringkasan hari ini
                </span>

                <h2>
                    Selamat datang, {{ $authUser->name }}
                </h2>

                <p>
                    {{ now()->translatedFormat('l, d F Y') }}.
                    Berikut kondisi operasional RNBeer POS saat ini.
                </p>
            </div>

            <a href="{{ route('admin.sales.pos') }}" class="btn btn-primary dashboard-primary-action">
                Buka Kasir
            </a>
        </section>

        {{-- Primary KPI --}}
        <section class="dashboard-section">
            <div class="row g-3">
                <div class="col-xl-3 col-sm-6">
                    <div class="dashboard-kpi is-sales">
                        <div class="dashboard-kpi-header">
                            <span>Penjualan Hari Ini</span>
                            <span class="dashboard-kpi-dot"></span>
                        </div>

                        <strong>
                            Rp {{ number_format($todaySales ?? 0, 0, ',', '.') }}
                        </strong>

                        <small>
                            Nilai transaksi selesai hari ini
                        </small>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    @if ($isAdmin)
                        <div class="dashboard-kpi is-profit">
                            <div class="dashboard-kpi-header">
                                <span>Laba Kotor Hari Ini</span>
                                <span class="dashboard-kpi-dot"></span>
                            </div>

                            <strong>
                                Rp {{ number_format($todayGrossProfit ?? 0, 0, ',', '.') }}
                            </strong>

                            <small>
                                Penjualan dikurangi HPP
                            </small>
                        </div>
                    @else
                        <div class="dashboard-kpi is-average">
                            <div class="dashboard-kpi-header">
                                <span>Rata-rata Transaksi</span>
                                <span class="dashboard-kpi-dot"></span>
                            </div>

                            <strong>
                                Rp {{ number_format($averageTransaction ?? 0, 0, ',', '.') }}
                            </strong>

                            <small>
                                Nilai rata-rata per transaksi
                            </small>
                        </div>
                    @endif
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="dashboard-kpi is-transaction">
                        <div class="dashboard-kpi-header">
                            <span>Transaksi Hari Ini</span>
                            <span class="dashboard-kpi-dot"></span>
                        </div>

                        <strong>
                            {{ number_format($todayTransactions ?? 0, 0, ',', '.') }}
                        </strong>

                        <small>
                            Transaksi penjualan selesai
                        </small>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="dashboard-kpi is-warning">
                        <div class="dashboard-kpi-header">
                            <span>Perlu Perhatian</span>
                            <span class="dashboard-kpi-dot"></span>
                        </div>

                        <strong>
                            {{ number_format($lowStockProducts ?? 0, 0, ',', '.') }}
                        </strong>

                        <small>
                            Produk habis atau stok menipis
                        </small>
                    </div>
                </div>
            </div>
        </section>

        {{-- Inventory and Purchase KPI --}}
        @if ($isAdmin)
            <section class="dashboard-section">
                <div class="dashboard-section-heading">
                    <div>
                        <h3>Persediaan dan Pembelian</h3>
                        <p>Ringkasan arus stok dan nilai persediaan hari ini.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-3 col-sm-6">
                        <div class="dashboard-secondary-kpi">
                            <span>Pembelian Hari Ini</span>

                            <strong>
                                Rp {{ number_format($todayPurchaseAmount ?? 0, 0, ',', '.') }}
                            </strong>

                            <small>
                                Nilai stok yang dibeli
                            </small>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6">
                        <div class="dashboard-secondary-kpi">
                            <span>Qty Dibeli Hari Ini</span>

                            <strong>
                                {{ number_format($todayPurchaseQty ?? 0, 0, ',', '.') }}
                            </strong>

                            <small>
                                Total unit stok masuk
                            </small>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6">
                        <div class="dashboard-secondary-kpi">
                            <span>Total Qty FIFO</span>

                            <strong>
                                {{ number_format($totalFifoStockQty ?? 0, 0, ',', '.') }}
                            </strong>

                            <small>
                                Unit tersedia dalam batch
                            </small>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6">
                        <div class="dashboard-secondary-kpi">
                            <span>Nilai Stok FIFO</span>

                            <strong>
                                Rp {{ number_format($totalFifoStockValue ?? 0, 0, ',', '.') }}
                            </strong>

                            <small>
                                Nilai modal persediaan aktif
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Quick Action --}}
        <section class="dashboard-panel dashboard-quick-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Aksi Cepat</h3>
                    <p>Akses fungsi yang paling sering digunakan.</p>
                </div>
            </div>

            <div class="dashboard-action-grid">
                <a href="{{ route('admin.sales.pos') }}" class="dashboard-action-item is-primary">
                    <span class="dashboard-action-label">Transaksi</span>
                    <strong>Penjualan Baru</strong>
                    <small>Buka kasir dan mulai transaksi</small>
                    <span class="dashboard-action-arrow">→</span>
                </a>

                <a href="{{ route('admin.sales.history') }}" class="dashboard-action-item">
                    <span class="dashboard-action-label">Penjualan</span>
                    <strong>Riwayat Transaksi</strong>
                    <small>Lihat transaksi dan struk terdahulu</small>
                    <span class="dashboard-action-arrow">→</span>
                </a>

                @if ($isAdmin)
                    <a href="{{ route('admin.purchases.create') }}" class="dashboard-action-item">
                        <span class="dashboard-action-label">Inventory</span>
                        <strong>Pembelian Stok</strong>
                        <small>Catat pembelian dari supplier</small>
                        <span class="dashboard-action-arrow">→</span>
                    </a>

                    <a href="{{ route('admin.products.create') }}" class="dashboard-action-item">
                        <span class="dashboard-action-label">Master Data</span>
                        <strong>Tambah Produk</strong>
                        <small>Daftarkan produk baru</small>
                        <span class="dashboard-action-arrow">→</span>
                    </a>

                    <a href="{{ route('admin.inventory.batches') }}" class="dashboard-action-item">
                        <span class="dashboard-action-label">Persediaan</span>
                        <strong>Batch FIFO</strong>
                        <small>Periksa batch dan nilai stok</small>
                        <span class="dashboard-action-arrow">→</span>
                    </a>

                    <a href="{{ route('admin.reports.sales') }}" class="dashboard-action-item">
                        <span class="dashboard-action-label">Laporan</span>
                        <strong>Laporan Penjualan</strong>
                        <small>Analisis transaksi dan pendapatan</small>
                        <span class="dashboard-action-arrow">→</span>
                    </a>
                @endif
            </div>
        </section>

        <div class="row g-3 dashboard-main-grid">
            {{-- Store Summary --}}
            <div class="col-xl-5">
                <section class="dashboard-panel h-100">
                    <div class="dashboard-panel-header">
                        <div>
                            <h3>Kondisi Toko</h3>
                            <p>Informasi operasional yang perlu dipantau.</p>
                        </div>
                    </div>

                    <div class="dashboard-summary-grid">
                        <div class="dashboard-summary-item">
                            <span>Produk Aktif</span>
                            <strong>{{ $totalProducts ?? 0 }}</strong>
                            <small>Produk</small>
                        </div>

                        <div class="dashboard-summary-item">
                            <span>Produk Habis</span>
                            <strong>{{ $outOfStockProducts ?? 0 }}</strong>
                            <small>Produk</small>
                        </div>

                        <div class="dashboard-summary-item">
                            <span>Rata-rata Transaksi</span>
                            <strong>
                                Rp {{ number_format($averageTransaction ?? 0, 0, ',', '.') }}
                            </strong>
                            <small>Per transaksi</small>
                        </div>

                        <div class="dashboard-summary-item">
                            <span>Void Penjualan</span>
                            <strong>{{ $todayVoidTransactions ?? 0 }}</strong>
                            <small>
                                Rp {{ number_format($todayVoidAmount ?? 0, 0, ',', '.') }}
                            </small>
                        </div>

                        @if ($isAdmin)
                            <div class="dashboard-summary-item">
                                <span>Void Pembelian</span>
                                <strong>{{ $todayVoidPurchaseTransactions ?? 0 }}</strong>
                                <small>
                                    Rp
                                    {{ number_format($todayVoidPurchaseAmount ?? 0, 0, ',', '.') }}
                                </small>
                            </div>

                            <div class="dashboard-summary-item">
                                <span>Total Batch FIFO</span>
                                <strong>{{ $totalFifoStockQty ?? 0 }}</strong>
                                <small>Unit aktif</small>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Payment Summary --}}
            <div class="col-xl-7">
                <section class="dashboard-panel h-100">
                    <div class="dashboard-panel-header">
                        <div>
                            <h3>Pembayaran Hari Ini</h3>
                            <p>Nominal yang diterima berdasarkan metode pembayaran.</p>
                        </div>

                        <a href="{{ route('admin.sales.history') }}" class="dashboard-panel-link">
                            Lihat transaksi
                        </a>
                    </div>

                    <div class="dashboard-payment-list">
                        @forelse ($paymentSummaryData as $payment)
                            @php
                                $method = strtolower($payment->payment_method);

                                $methodLabel = match ($method) {
                                    'cash' => 'Cash',
                                    'qris' => 'QRIS',
                                    'transfer' => 'Transfer',
                                    'split' => 'Split Payment',
                                    default => ucfirst($method),
                                };

                                $methodClass = match ($method) {
                                    'cash' => 'is-cash',
                                    'qris' => 'is-qris',
                                    'transfer' => 'is-transfer',
                                    'split' => 'is-split',
                                    default => 'is-default',
                                };
                            @endphp

                            <div class="dashboard-payment-item">
                                <div class="dashboard-payment-main">
                                    <div>
                                        <span class="payment-method-pill {{ $methodClass }}">
                                            {{ $methodLabel }}
                                        </span>

                                        <small>
                                            {{ $payment->total_transactions }} transaksi
                                        </small>
                                    </div>

                                    <strong>
                                        Rp
                                        {{ number_format($payment->total_amount, 0, ',', '.') }}
                                    </strong>
                                </div>

                                @if ($method === 'split')
                                    <div class="dashboard-split-detail">
                                        @foreach ([
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
        ] as $splitMethod => $splitLabel)
                                            @php
                                                $splitAmount = (float) $splitBreakdown->get($splitMethod, 0);
                                            @endphp

                                            @if ($splitAmount > 0)
                                                <div>
                                                    <span>{{ $splitLabel }}</span>

                                                    <strong>
                                                        Rp
                                                        {{ number_format($splitAmount, 0, ',', '.') }}
                                                    </strong>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="dashboard-empty-state">
                                <strong>Belum ada pembayaran</strong>
                                <p>Transaksi pembayaran hari ini akan tampil di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- Stock and Top Product --}}
        <div class="row g-3 dashboard-main-grid">
            <div class="col-lg-7">
                <section class="dashboard-panel h-100">
                    <div class="dashboard-panel-header">
                        <div>
                            <h3>Produk Perlu Restock</h3>
                            <p>Produk yang sudah habis atau mencapai stok minimum.</p>
                        </div>

                        <a href="{{ route('admin.inventory.low-stock') }}" class="dashboard-panel-link">
                            Lihat semua
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table dashboard-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Stok</th>
                                    <th>Minimum</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($lowStockList ?? [] as $product)
                                    <tr>
                                        <td>
                                            <div class="dashboard-table-primary">
                                                <strong>{{ $product->name }}</strong>
                                                <small>SKU {{ $product->sku }}</small>
                                            </div>
                                        </td>

                                        <td>
                                            <strong>{{ $product->stock }}</strong>
                                        </td>

                                        <td>{{ $product->minimum_stock }}</td>

                                        <td>
                                            @if ($product->stock <= 0)
                                                <span class="dashboard-status is-danger">
                                                    Habis
                                                </span>
                                            @else
                                                <span class="dashboard-status is-warning">
                                                    Menipis
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="dashboard-table-empty">
                                                Stok seluruh produk masih aman.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="dashboard-panel h-100">
                    <div class="dashboard-panel-header">
                        <div>
                            <h3>Produk Terlaris</h3>
                            <p>Peringkat penjualan produk hari ini.</p>
                        </div>
                    </div>

                    <div class="dashboard-ranking-list">
                        @forelse ($topProductsToday ?? [] as $item)
                            <div class="dashboard-ranking-item">
                                <span class="dashboard-ranking-number">
                                    {{ $loop->iteration }}
                                </span>

                                <div>
                                    <strong>{{ $item->product_name }}</strong>
                                    <small>Terjual hari ini</small>
                                </div>

                                <span class="dashboard-ranking-value">
                                    {{ $item->total_qty }} qty
                                </span>
                            </div>
                        @empty
                            <div class="dashboard-empty-state">
                                <strong>Belum ada produk terjual</strong>
                                <p>Peringkat produk akan muncul setelah ada transaksi.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- Latest Purchases --}}
        @if ($isAdmin)
            <section class="dashboard-panel dashboard-history-panel">
                <div class="dashboard-panel-header">
                    <div>
                        <h3>Pembelian Stok Terakhir</h3>
                        <p>Lima aktivitas pembelian stok terbaru.</p>
                    </div>

                    <a href="{{ route('admin.purchases.index') }}" class="dashboard-panel-link">
                        Lihat semua
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table dashboard-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No. Pembelian</th>
                                <th>Supplier</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($latestPurchases ?? [] as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.purchases.show', $purchase) }}"
                                            class="dashboard-record-link">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                    </td>

                                    <td>
                                        {{ $purchase->supplier?->name ?? '-' }}
                                    </td>

                                    <td>
                                        <span @class([
                                            'dashboard-void-value' => $purchase->status === 'void',
                                        ])>
                                            Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="dashboard-status
                                                {{ $purchase->status === 'completed' ? 'is-success' : 'is-danger' }}">
                                            {{ $purchase->status === 'completed' ? 'Selesai' : 'Void' }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="dashboard-table-empty">
                                            Belum ada pembelian stok.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Latest Sales --}}
        <section class="dashboard-panel dashboard-history-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Transaksi Penjualan Terakhir</h3>
                    <p>Sepuluh transaksi penjualan terbaru.</p>
                </div>

                <a href="{{ route('admin.sales.history') }}" class="dashboard-panel-link">
                    Lihat semua
                </a>
            </div>

            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($latestSales ?? [] as $sale)
                            @php
                                $saleMethodLabel = match ($sale->payment_method) {
                                    'cash' => 'Cash',
                                    'qris' => 'QRIS',
                                    'transfer' => 'Transfer',
                                    'split' => 'Split',
                                    default => ucfirst($sale->payment_method),
                                };
                            @endphp

                            <tr>
                                <td>
                                    <a href="{{ route('admin.sales.receipt', $sale) }}" class="dashboard-record-link">
                                        {{ $sale->invoice_number }}
                                    </a>
                                </td>

                                <td>
                                    {{ $sale->soldBy?->name ?? '-' }}
                                </td>

                                <td>
                                    <span @class([
                                        'dashboard-void-value' => $sale->status === 'void',
                                    ])>
                                        Rp {{ number_format($sale->total, 0, ',', '.') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="payment-method-pill is-compact">
                                        {{ $saleMethodLabel }}
                                    </span>
                                </td>

                                <td>
                                    <span
                                        class="dashboard-status
                                            {{ $sale->status === 'completed' ? 'is-success' : 'is-danger' }}">
                                        {{ $sale->status === 'completed' ? 'Selesai' : 'Void' }}
                                    </span>
                                </td>

                                <td>
                                    {{ $sale->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="dashboard-table-empty">
                                        Belum ada transaksi penjualan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
