@extends('layouts.admin')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')

@section('content')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Filter Laporan</h3>
        </div>

        <form action="{{ route('admin.reports.sales') }}" method="GET">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">
                            Tanggal Awal
                        </label>

                        <input type="date" name="start_date" id="start_date"
                            class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ request('start_date', $startDate) }}">

                        @error('start_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">
                            Tanggal Akhir
                        </label>

                        <input type="date" name="end_date" id="end_date"
                            class="form-control @error('end_date') is-invalid @enderror"
                            value="{{ request('end_date', $endDate) }}">

                        @error('end_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="payment_method" class="form-label">
                            Metode Pembayaran
                        </label>

                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="">Semua Metode</option>

                            <option value="cash" @selected(request('payment_method') === 'cash')>
                                Cash
                            </option>

                            <option value="qris" @selected(request('payment_method') === 'qris')>
                                QRIS
                            </option>

                            <option value="transfer" @selected(request('payment_method') === 'transfer')>
                                Transfer
                            </option>

                            <option value="split" @selected(request('payment_method') === 'split')>
                                Split
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="completed" @selected(request('status') === 'completed')>
                                Completed
                            </option>
                            <option value="void" @selected(request('status') === 'void')>
                                Void
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
                <a href="{{ route('admin.reports.sales') }}" class="btn btn-secondary">
                    Reset
                </a>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.reports.sales.export', request()->query()) }}" class="btn btn-success">
                        Export Excel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Tampilkan Laporan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>Rp {{ number_format($totalSales, 0, ',', '.') }}</h3>
                    <p>Total Penjualan</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</h3>
                    <p>Total Laba Kotor</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $totalTransactions }}</h3>
                    <p>Transaksi Completed</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $totalVoidTransactions }}</h3>
                    <p>Transaksi Void</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Ringkasan Pembayaran
            </h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <th>Total Transaksi</th>
                            <th>Total Nominal</th>
                            <th>Rincian Pembayaran Split</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($paymentSummary as $payment)
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

                                        @case('split')
                                            <span class="badge bg-dark">Split</span>
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
                                        Belum ada data pembayaran.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    Detail Transaksi
                </h3>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Kasir</th>
                                <th>Total</th>
                                <th>Bayar</th>
                                <th>Kembalian</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($sales as $sale)
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
                                        Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($sale->change_amount, 0, ',', '.') }}
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

                                            @case('split')
                                                @php
                                                    $splitAmounts = $sale->payments
                                                        ->groupBy('payment_method')
                                                        ->map(fn($payments) => $payments->sum('amount'));

                                                    $cashAmount = max(
                                                        0,
                                                        (float) $splitAmounts->get('cash', 0) -
                                                            (float) $sale->change_amount,
                                                    );
                                                @endphp

                                                <div class="d-flex flex-column gap-1">
                                                    <div>
                                                        <span class="badge bg-dark">
                                                            Split
                                                        </span>
                                                    </div>

                                                    @if ($cashAmount > 0)
                                                        <small class="text-nowrap">
                                                            <span class="badge bg-success">
                                                                Cash
                                                            </span>

                                                            Rp {{ number_format($cashAmount, 0, ',', '.') }}
                                                        </small>
                                                    @endif

                                                    @if ($splitAmounts->get('qris', 0) > 0)
                                                        <small class="text-nowrap">
                                                            <span class="badge bg-primary">
                                                                QRIS
                                                            </span>

                                                            Rp
                                                            {{ number_format($splitAmounts->get('qris', 0), 0, ',', '.') }}
                                                        </small>
                                                    @endif

                                                    @if ($splitAmounts->get('transfer', 0) > 0)
                                                        <small class="text-nowrap">
                                                            <span class="badge bg-info">
                                                                Transfer
                                                            </span>

                                                            Rp
                                                            {{ number_format($splitAmounts->get('transfer', 0), 0, ',', '.') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @break

                                            @default
                                                <span class="badge bg-secondary">
                                                    {{ strtoupper($sale->payment_method) }}
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
                                        {{ $sale->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Tidak ada transaksi sesuai filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($sales->hasPages())
                    <div class="card-footer">
                        {{ $sales->links() }}
                    </div>
                @endif
            </div>

        @endsection
