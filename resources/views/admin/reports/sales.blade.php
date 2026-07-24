@extends('layouts.admin')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('page-subtitle', 'Analisis omzet, laba kotor, pembayaran, dan transaksi berdasarkan periode tertentu.')

@section('page-actions')
    <a href="{{ route('admin.reports.sales.export', request()->query()) }}" class="btn btn-success">
        Export Excel
    </a>
@endsection

@section('content')
    @php
        $resolvedStartDate = request('start_date', $startDate);
        $resolvedEndDate = request('end_date', $endDate);

        $filterKeys = ['start_date', 'end_date', 'payment_method', 'status'];

        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();

        $hasActiveFilters = $activeFilterCount > 0;

        $paymentLabels = [
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            'split' => 'Split Payment',
        ];

        $statusLabels = [
            'completed' => 'Selesai',
            'void' => 'Void',
        ];

        $grossMargin = $totalSales > 0 ? ($totalGrossProfit / $totalSales) * 100 : 0;

        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
    @endphp

    <div class="crud-page sales-report-page">

        {{-- Summary --}}
        <section class="sales-report-summary">
            <article class="sales-report-summary-card">
                <span>Total Penjualan</span>

                <strong>
                    Rp {{ number_format($totalSales, 0, ',', '.') }}
                </strong>

                <small>
                    Nilai transaksi selesai pada periode ini
                </small>
            </article>

            <article class="sales-report-summary-card">
                <span>Total Laba Kotor</span>

                <strong class="is-success">
                    Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}
                </strong>

                <small>
                    Margin kotor {{ number_format($grossMargin, 2, ',', '.') }}%
                </small>
            </article>

            <article class="sales-report-summary-card">
                <span>Transaksi Selesai</span>

                <strong class="is-primary">
                    {{ number_format($totalTransactions, 0, ',', '.') }}
                </strong>

                <small>
                    Rata-rata Rp
                    {{ number_format($averageTransaction, 0, ',', '.') }}
                </small>
            </article>

            <article class="sales-report-summary-card">
                <span>Transaksi Void</span>

                <strong class="is-danger">
                    {{ number_format($totalVoidTransactions, 0, ',', '.') }}
                </strong>

                <small>
                    Transaksi yang telah dibatalkan
                </small>
            </article>
        </section>

        {{-- Filter --}}
        <section class="crud-panel sales-report-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Laporan</h2>

                    <p>
                        Tentukan periode, metode pembayaran, dan status transaksi
                        yang ingin dianalisis.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.reports.sales') }}" method="GET">
                <div class="sales-report-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="start_date" class="form-label">
                                Tanggal Awal
                            </label>

                            <input type="date" name="start_date" id="start_date"
                                class="form-control crud-form-control
                                    @error('start_date') is-invalid @enderror"
                                value="{{ $resolvedStartDate }}">

                            @error('start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="end_date" class="form-label">
                                Tanggal Akhir
                            </label>

                            <input type="date" name="end_date" id="end_date"
                                class="form-control crud-form-control
                                    @error('end_date') is-invalid @enderror"
                                value="{{ $resolvedEndDate }}">

                            @error('end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="payment_method" class="form-label">
                                Metode Pembayaran
                            </label>

                            <select name="payment_method" id="payment_method" class="form-select crud-form-control">
                                <option value="">
                                    Semua metode
                                </option>

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
                                    Split Payment
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="status" class="form-label">
                                Status Transaksi
                            </label>

                            <select name="status" id="status" class="form-select crud-form-control">
                                <option value="">
                                    Semua status
                                </option>

                                <option value="completed" @selected(request('status') === 'completed')>
                                    Selesai
                                </option>

                                <option value="void" @selected(request('status') === 'void')>
                                    Void
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="sales-report-filter-footer">
                    <div class="sales-report-period">
                        <span>Periode laporan</span>

                        <strong>
                            {{ \Carbon\Carbon::parse($resolvedStartDate)->format('d M Y') }}
                            –
                            {{ \Carbon\Carbon::parse($resolvedEndDate)->format('d M Y') }}
                        </strong>
                    </div>

                    <div class="sales-report-filter-actions">
                        <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Tampilkan Laporan
                        </button>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="sales-report-active-filters">
                    <span class="sales-report-filter-label">
                        Filter:
                    </span>

                    @if (request()->filled('start_date'))
                        <span class="sales-report-filter-chip">
                            Mulai:
                            {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if (request()->filled('end_date'))
                        <span class="sales-report-filter-chip">
                            Sampai:
                            {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if (request()->filled('payment_method'))
                        <span class="sales-report-filter-chip">
                            Metode:
                            {{ $paymentLabels[request('payment_method')] ?? request('payment_method') }}
                        </span>
                    @endif

                    @if (request()->filled('status'))
                        <span class="sales-report-filter-chip">
                            Status:
                            {{ $statusLabels[request('status')] ?? request('status') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Payment summary --}}
        <section class="crud-panel sales-payment-summary-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Ringkasan Pembayaran</h2>

                    <p>
                        Distribusi nominal dan jumlah transaksi berdasarkan
                        metode pembayaran.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $paymentSummary->count() }} metode
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table sales-payment-summary-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <th>Jumlah Transaksi</th>
                            <th>Total Nominal</th>
                            <th>Rata-rata Transaksi</th>
                            <th>Rincian Split Payment</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($paymentSummary as $payment)
                            @php
                                $method = strtolower($payment->payment_method ?? '');

                                $methodLabel = $paymentLabels[$method] ?? strtoupper($method ?: '-');

                                $methodClass = match ($method) {
                                    'cash' => 'is-cash',
                                    'qris' => 'is-qris',
                                    'transfer' => 'is-transfer',
                                    'split' => 'is-split',
                                    default => 'is-default',
                                };

                                $averagePayment =
                                    $payment->total_transactions > 0
                                        ? $payment->total_amount / $payment->total_transactions
                                        : 0;

                                $splitCash = max((float) $splitPaymentBreakdown->get('cash', 0), 0);

                                $splitQris = max((float) $splitPaymentBreakdown->get('qris', 0), 0);

                                $splitTransfer = max((float) $splitPaymentBreakdown->get('transfer', 0), 0);
                            @endphp

                            <tr>
                                <td>
                                    <span class="sales-report-payment-badge {{ $methodClass }}">
                                        {{ $methodLabel }}
                                    </span>
                                </td>

                                <td>
                                    <div class="sales-report-number-cell">
                                        <strong>
                                            {{ number_format($payment->total_transactions, 0, ',', '.') }}
                                        </strong>

                                        <small>transaksi</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-report-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($payment->total_amount, 0, ',', '.') }}
                                        </strong>

                                        <small>total penerimaan</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-report-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($averagePayment, 0, ',', '.') }}
                                        </strong>

                                        <small>per transaksi</small>
                                    </div>
                                </td>

                                <td>
                                    @if ($method === 'split')
                                        <div class="sales-report-split-list">
                                            @if ($splitCash > 0)
                                                <div>
                                                    <span class="sales-report-payment-badge is-cash">
                                                        Cash
                                                    </span>

                                                    <strong>
                                                        Rp
                                                        {{ number_format($splitCash, 0, ',', '.') }}
                                                    </strong>
                                                </div>
                                            @endif

                                            @if ($splitQris > 0)
                                                <div>
                                                    <span class="sales-report-payment-badge is-qris">
                                                        QRIS
                                                    </span>

                                                    <strong>
                                                        Rp
                                                        {{ number_format($splitQris, 0, ',', '.') }}
                                                    </strong>
                                                </div>
                                            @endif

                                            @if ($splitTransfer > 0)
                                                <div>
                                                    <span class="sales-report-payment-badge is-transfer">
                                                        Transfer
                                                    </span>

                                                    <strong>
                                                        Rp
                                                        {{ number_format($splitTransfer, 0, ',', '.') }}
                                                    </strong>
                                                </div>
                                            @endif

                                            @if ($splitCash <= 0 && $splitQris <= 0 && $splitTransfer <= 0)
                                                <span class="sales-report-empty-value">
                                                    Tidak ada rincian
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="sales-report-empty-value">
                                            Tidak berlaku
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="crud-empty-state">
                                        <strong>Belum ada data pembayaran</strong>

                                        <p>
                                            Tidak ada pembayaran yang sesuai dengan
                                            periode dan filter laporan.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Transaction detail --}}
        <section class="crud-panel sales-report-detail-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Detail Transaksi</h2>

                    <p>
                        Daftar transaksi penjualan berdasarkan periode dan
                        filter laporan.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($sales->total(), 0, ',', '.') }}
                    transaksi
                </span>
            </div>

            <div class="sales-report-result-summary">
                <span>
                    Menampilkan
                    <strong>{{ $sales->firstItem() ?? 0 }}</strong>
                    sampai
                    <strong>{{ $sales->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $sales->total() }}</strong>
                    transaksi
                </span>

                @if ($sales->hasPages())
                    <span>
                        Halaman {{ $sales->currentPage() }}
                        dari {{ $sales->lastPage() }}
                    </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table crud-table sales-report-detail-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Transaksi</th>
                            <th>Kasir</th>
                            <th>Nilai Transaksi</th>
                            <th>Pembayaran</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($sales as $sale)
                            @php
                                $isVoid = $sale->status === 'void';

                                $paymentMethod = strtolower($sale->payment_method ?? '');

                                $paymentLabel = $paymentLabels[$paymentMethod] ?? strtoupper($paymentMethod ?: '-');

                                $paymentClass = match ($paymentMethod) {
                                    'cash' => 'is-cash',
                                    'qris' => 'is-qris',
                                    'transfer' => 'is-transfer',
                                    'split' => 'is-split',
                                    default => 'is-default',
                                };

                                $splitAmounts = $sale->payments
                                    ->groupBy('payment_method')
                                    ->map(fn($payments) => $payments->sum('amount'));

                                $cashAmount = max(
                                    0,
                                    (float) $splitAmounts->get('cash', 0) - (float) $sale->change_amount,
                                );

                                $qrisAmount = max(0, (float) $splitAmounts->get('qris', 0));

                                $transferAmount = max(0, (float) $splitAmounts->get('transfer', 0));
                            @endphp

                            <tr @class(['is-void' => $isVoid])>
                                <td>
                                    <div class="sales-report-invoice-cell">
                                        <a href="{{ route('admin.sales.receipt', $sale) }}">
                                            {{ $sale->invoice_number }}
                                        </a>

                                        <span>
                                            {{ $sale->created_at?->format('d M Y') ?? '-' }}
                                        </span>

                                        <small>
                                            {{ $sale->created_at?->format('H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-report-cashier-cell">
                                        <strong>
                                            {{ $sale->soldBy?->name ?? 'User tidak ditemukan' }}
                                        </strong>

                                        <small>
                                            Kasir transaksi
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-report-transaction-value">
                                        <strong>
                                            Rp
                                            {{ number_format($sale->total, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            {{ $isVoid ? 'Transaksi dibatalkan' : 'Total penjualan' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-report-payment-values">
                                        <div>
                                            <span>Dibayar</span>

                                            <strong>
                                                Rp
                                                {{ number_format($sale->paid_amount, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Kembalian</span>

                                            <strong>
                                                Rp
                                                {{ number_format($sale->change_amount, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="sales-report-payment-badge {{ $paymentClass }}">
                                        {{ $paymentLabel }}
                                    </span>

                                    @if ($paymentMethod === 'split')
                                        <div class="sales-report-detail-split">
                                            @if ($cashAmount > 0)
                                                <span>
                                                    Cash:
                                                    <strong>
                                                        Rp
                                                        {{ number_format($cashAmount, 0, ',', '.') }}
                                                    </strong>
                                                </span>
                                            @endif

                                            @if ($qrisAmount > 0)
                                                <span>
                                                    QRIS:
                                                    <strong>
                                                        Rp
                                                        {{ number_format($qrisAmount, 0, ',', '.') }}
                                                    </strong>
                                                </span>
                                            @endif

                                            @if ($transferAmount > 0)
                                                <span>
                                                    Transfer:
                                                    <strong>
                                                        Rp
                                                        {{ number_format($transferAmount, 0, ',', '.') }}
                                                    </strong>
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if ($sale->status === 'completed')
                                        <span class="crud-status is-success">
                                            Selesai
                                        </span>
                                    @elseif ($sale->status === 'void')
                                        <span class="crud-status is-danger">
                                            Void
                                        </span>
                                    @else
                                        <span class="crud-status is-muted">
                                            {{ ucfirst($sale->status ?? '-') }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.sales.receipt', $sale) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Lihat Struk
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crud-empty-state">
                                        <strong>Transaksi tidak ditemukan</strong>

                                        <p>
                                            Tidak ada transaksi yang sesuai dengan
                                            periode dan filter laporan.
                                        </p>

                                        <a href="{{ route('admin.reports.sales') }}"
                                            class="btn btn-outline-primary btn-sm">
                                            Reset Filter
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $sales->firstItem() }}
                        sampai {{ $sales->lastItem() }}
                        dari {{ $sales->total() }} transaksi
                    </span>

                    {{ $sales->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
