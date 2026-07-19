@extends('layouts.admin')

@section('title', 'Dashboard Penjualan')
@section('page-title', 'Dashboard Penjualan')

@section('content')

    {{-- Stats Cards --}}
    {{-- Stats Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>Rp {{ number_format($todaySales, 0, ',', '.') }}</h3>
                    <p>Penjualan Hari Ini</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format($todayGrossProfit ?? 0, 0, ',', '.') }}</h3>
                    <p>Laba Kotor Hari Ini</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $todayTransactions }}</h3>
                    <p>Transaksi Penjualan Hari Ini</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $lowStockProducts }}</h3>
                    <p>Stok Produk Menipis</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Inventory & Purchase Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>Rp {{ number_format($todayPurchaseAmount ?? 0, 0, ',', '.') }}</h3>
                    <p>Pembelian Stok Hari Ini</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-secondary">
                <div class="inner">
                    <h3>{{ $todayPurchaseQty ?? 0 }}</h3>
                    <p>Qty Pembelian Stok Hari Ini</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format($totalFifoStockValue ?? 0, 0, ',', '.') }}</h3>
                    <p>Nilai Stok FIFO</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>{{ $todayVoidPurchaseTransactions ?? 0 }}</h3>
                    <p>Void Pembelian Stok Hari Ini</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Aksi Cepat</h3>
        </div>

        <div class="card-body d-flex flex-wrap gap-2">
            <a href="{{ route('admin.sales.pos') }}" class="btn btn-primary btn-sm">
                + Transaksi Baru
            </a>

            <a href="{{ route('admin.products.create') }}" class="btn btn-success btn-sm">
                + Tambah Produk
            </a>

            <a href="{{ route('admin.purchases.create') }}" class="btn btn-warning btn-sm">
                + Pembelian Stok
            </a>

            <a href="{{ route('admin.inventory.batches') }}" class="btn btn-info btn-sm">
                Batch FIFO
            </a>

            <a href="{{ route('admin.sales.history') }}" class="btn btn-secondary btn-sm">
                Riwayat Penjualan
            </a>

            <a href="{{ route('admin.reports.sales') }}" class="btn btn-dark btn-sm">
                Laporan Penjualan
            </a>

            <a href="{{ route('admin.reports.purchases') }}" class="btn btn-outline-dark btn-sm">
                Laporan Pembelian
            </a>
        </div>
    </div>

    {{-- Ringkasan POS --}}
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Ringkasan POS</h3>
        </div>

        <div class="card-body">
            Selamat datang, <strong>{{ auth()->user()->name }}</strong>.
            Berikut ringkasan aktivitas toko per hari ini
            <strong>{{ now()->translatedFormat('l, d/m/Y') }}</strong>.

            <hr>

            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <strong>Total Produk Aktif</strong>
                    <br>
                    <span>{{ $totalProducts }} Produk</span>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <strong>Rata-rata Transaksi</strong>
                    <br>
                    <span>
                        Rp {{ number_format($averageTransaction ?? 0, 0, ',', '.') }}
                    </span>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <strong>Produk Habis</strong>
                    <br>
                    <span>{{ $outOfStockProducts ?? 0 }} Produk</span>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <strong>Total Qty FIFO</strong>
                    <br>
                    <span>{{ $totalFifoStockQty ?? 0 }} Qty</span>
                    <br>
                    <small class="text-muted">
                        Rp {{ number_format($totalFifoStockValue ?? 0, 0, ',', '.') }}
                    </small>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <strong>Void Penjualan Hari Ini</strong>
                    <br>
                    <span>{{ $todayVoidTransactions ?? 0 }} Transaksi</span>
                    <br>
                    <small class="text-muted">
                        Rp {{ number_format($todayVoidAmount ?? 0, 0, ',', '.') }}
                    </small>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <strong>Void Pembelian Hari Ini</strong>
                    <br>
                    <span>{{ $todayVoidPurchaseTransactions ?? 0 }} Transaksi</span>
                    <br>
                    <small class="text-muted">
                        Rp {{ number_format($todayVoidPurchaseAmount ?? 0, 0, ',', '.') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Summary --}}
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Ringkasan Pembayaran Hari Ini
            </h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <th>Total Transaksi</th>
                            <th>Total Nominal</th>
                            <th>Rincian Pembayaran Split</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($paymentSummary ?? [] as $payment)
                            <tr>
                                <td>
                                    @switch($payment->payment_method)
                                        @case('cash')
                                            <span class="badge bg-success">
                                                Cash
                                            </span>
                                        @break

                                        @case('qris')
                                            <span class="badge bg-primary">
                                                QRIS
                                            </span>
                                        @break

                                        @case('transfer')
                                            <span class="badge bg-info">
                                                Transfer
                                            </span>
                                        @break

                                        @case('split')
                                            <span class="badge bg-dark">
                                                Split
                                            </span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($payment->payment_method) }}
                                            </span>
                                    @endswitch
                                </td>

                                <td>
                                    {{ $payment->total_transactions }} Transaksi
                                </td>

                                <td>
                                    <strong>
                                        Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    @if ($payment->payment_method === 'split')
                                        <div class="d-flex flex-column gap-1">
                                            <div>
                                                <span class="badge bg-success">
                                                    Cash
                                                </span>

                                                <span class="ms-1">
                                                    Rp
                                                    {{ number_format($splitPaymentBreakdown->get('cash', 0), 0, ',', '.') }}
                                                </span>
                                            </div>

                                            <div>
                                                <span class="badge bg-primary">
                                                    QRIS
                                                </span>

                                                <span class="ms-1">
                                                    Rp
                                                    {{ number_format($splitPaymentBreakdown->get('qris', 0), 0, ',', '.') }}
                                                </span>
                                            </div>

                                            <div>
                                                <span class="badge bg-info">
                                                    Transfer
                                                </span>

                                                <span class="ms-1">
                                                    Rp
                                                    {{ number_format($splitPaymentBreakdown->get('transfer', 0), 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">
                                            -
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Belum ada pembayaran hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Low Stock & Top Products --}}
        <div class="row g-3 mb-3">
            {{-- Low Stock Table --}}
            <div class="col-lg-7">
                <div class="card h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title">
                            Produk Stok Menipis
                        </h3>

                        <div class="card-tools">
                            <span class="badge bg-warning">
                                {{ $lowStockProducts }} Produk
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Stok</th>
                                        <th>Minimum</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($lowStockList as $product)
                                        <tr>
                                            <td>
                                                <strong>{{ $product->name }}</strong>
                                                <br>

                                                <small class="text-muted">
                                                    SKU: {{ $product->sku }}
                                                </small>
                                            </td>

                                            <td>{{ $product->stock }}</td>

                                            <td>{{ $product->minimum_stock }}</td>

                                            <td>
                                                @if ($product->stock == 0)
                                                    <span class="badge bg-danger">
                                                        Habis
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        Menipis
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                Tidak ada produk dengan stok menipis.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Products --}}
            <div class="col-lg-5">
                <div class="card h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title">
                            Produk Terlaris Hari Ini
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Qty Terjual</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($topProductsToday ?? [] as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->product_name }}</strong>
                                            </td>

                                            <td>
                                                {{ $item->total_qty }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-3">
                                                Belum ada produk terjual hari ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Latest Purchases --}}
        <div class="card mt-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">
                    5 Pembelian Stok Terakhir
                </h3>

                <a href="{{ route('admin.purchases.index') }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
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
                            @forelse ($latestPurchases as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.purchases.show', $purchase) }}"
                                            class="fw-bold text-decoration-none">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                    </td>

                                    <td>
                                        {{ $purchase->supplier?->name ?? '-' }}
                                    </td>

                                    <td>
                                        @if ($purchase->status === 'void')
                                            <span class="text-decoration-line-through text-muted">
                                                Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                            </span>
                                        @else
                                            Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                        @endif
                                    </td>

                                    <td>
                                        @if ($purchase->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-danger">Void</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Belum ada pembelian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- Latest Sales --}}
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    10 Transaksi Terakhir
                </h3>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Kasir</th>
                                <th>Total</th>
                                <th>Metode Pembayaran</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($latestSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.sales.receipt', $sale) }}"
                                            class="fw-bold text-decoration-none">
                                            {{ $sale->invoice_number }}
                                        </a>
                                    </td>

                                    <td>
                                        {{ $sale->soldBy?->name ?? '-' }}
                                    </td>

                                    <td>
                                        @if ($sale->status === 'void')
                                            <span class="text-decoration-line-through text-muted">
                                                Rp {{ number_format($sale->total, 0, ',', '.') }}
                                            </span>
                                        @else
                                            Rp {{ number_format($sale->total, 0, ',', '.') }}
                                        @endif
                                    </td>

                                    <td>
                                        @switch($sale->payment_method)
                                            @case('cash')
                                                <span class="badge bg-success">
                                                    Cash
                                                </span>
                                            @break

                                            @case('qris')
                                                <span class="badge bg-primary">
                                                    QRIS
                                                </span>
                                            @break

                                            @case('transfer')
                                                <span class="badge bg-info">
                                                    Transfer
                                                </span>
                                            @break

                                            @default
                                                <span class="badge bg-secondary">
                                                    {{ $sale->payment_method }}
                                                </span>
                                        @endswitch
                                    </td>

                                    <td>
                                        @if ($sale->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-danger">Void</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $sale->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Belum ada transaksi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @endsection

