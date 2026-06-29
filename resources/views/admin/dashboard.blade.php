@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')

@section('content')

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
                <p>Transaksi Hari Ini</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3>{{ $lowStockProducts }}</h3>
                <p>Stok Menipis</p>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Aksi Cepat</h3>
    </div>

    <div class="card-body">
        <a href="#" class="btn btn-primary btn-sm">
            + Transaksi Baru
        </a>

        <a href="#" class="btn btn-success btn-sm">
            + Tambah Produk
        </a>

        <a href="#" class="btn btn-warning btn-sm">
            + Pembelian Stok
        </a>

        <a href="#" class="btn btn-secondary btn-sm">
            Lihat Laporan
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
        Berikut ringkasan aktivitas toko hari ini.

        <hr>

        <div class="row">
            <div class="col-md-3 col-6">
                <strong>Total Produk</strong>
                <br>
                <span>{{ $totalProducts }} Produk</span>
            </div>

            <div class="col-md-3 col-6">
                <strong>Rata-rata Transaksi</strong>
                <br>
                <span>
                    Rp {{ number_format($averageTransaction ?? 0, 0, ',', '.') }}
                </span>
            </div>

            <div class="col-md-3 col-6">
                <strong>Produk Habis</strong>
                <br>
                <span>{{ $outOfStockProducts ?? 0 }} Produk</span>
            </div>

            <div class="col-md-3 col-6">
                <strong>Tanggal</strong>
                <br>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Payment Summary --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Ringkasan Pembayaran Hari Ini</h3>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Metode Pembayaran</th>
                        <th>Total Transaksi</th>
                        <th>Total Nominal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($paymentSummary ?? [] as $payment)
                        <tr>
                            <td>
                                @switch($payment->payment_method)
                                    @case('cash')
                                        <span class="badge bg-success">Cash</span>
                                        @break

                                    @case('qris')
                                        <span class="badge bg-primary">QRIS</span>
                                        @break

                                    @case('transfer')
                                        <span class="badge bg-info">Transfer</span>
                                        @break

                                    @default
                                        <span class="badge bg-secondary">
                                            {{ $payment->payment_method }}
                                        </span>
                                @endswitch
                            </td>

                            <td>{{ $payment->total_transactions }}</td>

                            <td>
                                Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                Belum ada pembayaran hari ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    {{-- Low Stock Table --}}
    <div class="col-lg-7">
        <div class="card">
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
        <div class="card">
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
                        <th>Tanggal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($latestSales as $sale)
                        <tr>
                            <td>
                                <strong>
                                    {{ $sale->invoice_number }}
                                </strong>
                            </td>

                            <td>
                                {{ $sale->soldBy?->name ?? '-' }}
                            </td>

                            <td>
                                Rp {{ number_format($sale->total, 0, ',', '.') }}
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
                                {{ $sale->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
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