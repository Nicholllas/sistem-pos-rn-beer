@extends('layouts.admin')

@section('title', 'Riwayat Penjualan')
@section('page-title', 'Riwayat Penjualan')
@section('page-subtitle', 'Cari, filter, dan periksa kembali transaksi penjualan yang telah diproses.')

@section('page-actions')
    @if (auth()->user()->isAdmin())
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-secondary">
            Laporan Penjualan
        </a>
    @endif

    <a href="{{ route('admin.sales.pos') }}" class="btn btn-primary">
        Transaksi Baru
    </a>
@endsection

@section('content')
    @php
        $filterKeys = ['search', 'start_date', 'end_date', 'cashier_id', 'status', 'payment_method'];

        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();

        $hasActiveFilters = $activeFilterCount > 0;

        $selectedCashier = request()->filled('cashier_id')
            ? $cashiers->firstWhere('id', (int) request('cashier_id'))
            : null;

        $statusLabels = [
            'completed' => 'Selesai',
            'void' => 'Void',
        ];

        $paymentLabels = [
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            'split' => 'Split Payment',
        ];
    @endphp

    <div class="crud-page sales-history-page">

        {{-- Filter --}}
        <section class="crud-panel sales-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Transaksi</h2>

                    <p>
                        Gunakan satu atau beberapa filter untuk menemukan
                        transaksi tertentu.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form method="GET" action="{{ url()->current() }}" class="sales-filter-form">
                <div class="sales-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">
                                Nomor Invoice
                            </label>

                            <input type="search" name="search" id="search" class="form-control crud-form-control"
                                value="{{ request('search') }}" placeholder="Contoh: INV-20260724-001" autocomplete="off">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="start_date" class="form-label">
                                Tanggal Awal
                            </label>

                            <input type="date" name="start_date" id="start_date"
                                class="form-control crud-form-control
                                    @error('start_date') is-invalid @enderror"
                                value="{{ request('start_date') }}">

                            @error('start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="end_date" class="form-label">
                                Tanggal Akhir
                            </label>

                            <input type="date" name="end_date" id="end_date"
                                class="form-control crud-form-control
                                    @error('end_date') is-invalid @enderror"
                                value="{{ request('end_date') }}">

                            @error('end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="cashier_id" class="form-label">
                                Kasir
                            </label>

                            <select name="cashier_id" id="cashier_id" class="form-select crud-form-control">
                                <option value="">
                                    Semua kasir
                                </option>

                                @foreach ($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" @selected((string) request('cashier_id') === (string) $cashier->id)>
                                        {{ $cashier->name }}

                                        @if (!$cashier->is_active)
                                            — Nonaktif
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
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

                        <div class="col-12 col-sm-6 col-lg-3">
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

                        <div class="col-12 col-lg-6 sales-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                                    Hapus Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="sales-active-filters">
                    <span class="sales-active-filter-label">
                        Filter:
                    </span>

                    @if (request()->filled('search'))
                        <span class="sales-filter-chip">
                            Invoice: {{ request('search') }}
                        </span>
                    @endif

                    @if (request()->filled('start_date'))
                        <span class="sales-filter-chip">
                            Mulai:
                            {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if (request()->filled('end_date'))
                        <span class="sales-filter-chip">
                            Sampai:
                            {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if ($selectedCashier)
                        <span class="sales-filter-chip">
                            Kasir: {{ $selectedCashier->name }}
                        </span>
                    @endif

                    @if (request()->filled('status'))
                        <span class="sales-filter-chip">
                            Status:
                            {{ $statusLabels[request('status')] ?? request('status') }}
                        </span>
                    @endif

                    @if (request()->filled('payment_method'))
                        <span class="sales-filter-chip">
                            Metode:
                            {{ $paymentLabels[request('payment_method')] ?? request('payment_method') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Result --}}
        <section class="crud-panel sales-result-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Transaksi</h2>

                    <p>
                        @if ($hasActiveFilters)
                            Hasil transaksi berdasarkan filter yang diterapkan.
                        @else
                            Seluruh transaksi penjualan terbaru.
                        @endif
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($sales->total(), 0, ',', '.') }}
                    transaksi
                </span>
            </div>

            <div class="sales-result-summary">
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
                <table class="table crud-table sales-history-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Transaksi</th>
                            <th>Kasir</th>
                            <th>Total</th>
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

                                $paymentLabel = match ($paymentMethod) {
                                    'cash' => 'Cash',
                                    'qris' => 'QRIS',
                                    'transfer' => 'Transfer',
                                    'split' => 'Split',
                                    default => strtoupper($sale->payment_method ?? '-'),
                                };

                                $paymentClass = match ($paymentMethod) {
                                    'cash' => 'is-cash',
                                    'qris' => 'is-qris',
                                    'transfer' => 'is-transfer',
                                    'split' => 'is-split',
                                    default => 'is-default',
                                };
                            @endphp

                            <tr @class(['is-void' => $isVoid])>
                                <td>
                                    <div class="sales-invoice-cell">
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
                                    <div class="sales-cashier-cell">
                                        <span class="sales-cashier-avatar">
                                            {{ strtoupper(mb_substr($sale->soldBy?->name ?? 'U', 0, 1)) }}
                                        </span>

                                        <div>
                                            <strong>
                                                {{ $sale->soldBy?->name ?? 'User tidak ditemukan' }}
                                            </strong>

                                            <small>Kasir</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-total-cell">
                                        <strong>
                                            Rp {{ number_format($sale->total, 0, ',', '.') }}
                                        </strong>

                                        @if ($isVoid)
                                            <small>Transaksi dibatalkan</small>
                                        @else
                                            <small>Total transaksi</small>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="sales-payment-cell">
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
                                    <span class="sales-payment-badge {{ $paymentClass }}">
                                        {{ $paymentLabel }}
                                    </span>
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
                                            {{ ucfirst($sale->status ?? 'Tidak diketahui') }}
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
                                        <span class="crud-empty-avatar">
                                            R
                                        </span>

                                        @if ($hasActiveFilters)
                                            <strong>Transaksi tidak ditemukan</strong>

                                            <p>
                                                Tidak ada transaksi yang sesuai dengan
                                                kombinasi filter tersebut.
                                            </p>

                                            <a href="{{ url()->current() }}" class="btn btn-outline-primary btn-sm">
                                                Hapus Filter
                                            </a>
                                        @else
                                            <strong>Belum ada transaksi</strong>

                                            <p>
                                                Transaksi yang telah diproses dari halaman
                                                kasir akan muncul di sini.
                                            </p>

                                            <a href="{{ route('admin.sales.pos') }}" class="btn btn-primary btn-sm">
                                                Buat Transaksi
                                            </a>
                                        @endif
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
