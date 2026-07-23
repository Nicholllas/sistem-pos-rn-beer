@extends('layouts.admin')

@section('title', 'Riwayat Penjualan')
@section('page-title', 'Riwayat Penjualan')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title mb-0">
                Riwayat Penjualan
            </h3>
        </div>

        {{-- Filter --}}
        <div class="card-body border-bottom">

            <form method="GET" action="{{ url()->current() }}">

                <div class="row g-3">

                    {{-- Nomor invoice --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="search" class="form-label">
                            Nomor Invoice
                        </label>

                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ request('search') }}" placeholder="Cari nomor invoice..." autocomplete="off">
                    </div>

                    {{-- Tanggal awal --}}
                    <div class="col-12 col-md-6 col-xl-2">
                        <label for="start_date" class="form-label">
                            Tanggal Awal
                        </label>

                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ request('start_date') }}">
                    </div>

                    {{-- Tanggal akhir --}}
                    <div class="col-12 col-md-6 col-xl-2">
                        <label for="end_date" class="form-label">
                            Tanggal Akhir
                        </label>

                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ request('end_date') }}">
                    </div>

                    {{-- Kasir --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="cashier_id" class="form-label">
                            Kasir
                        </label>

                        <select name="cashier_id" id="cashier_id" class="form-select">
                            <option value="">
                                Semua Kasir
                            </option>

                            @foreach ($cashiers as $cashier)
                                <option value="{{ $cashier->id }}" @selected((string) request('cashier_id') === (string) $cashier->id)>
                                    {{ $cashier->name }}

                                    @if (!$cashier->is_active)
                                        (Nonaktif)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="col-12 col-md-6 col-xl-2">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">
                                Semua Status
                            </option>

                            <option value="completed" @selected(request('status') === 'completed')>
                                Completed
                            </option>

                            <option value="void" @selected(request('status') === 'void')>
                                Void
                            </option>
                        </select>
                    </div>

                    {{-- Metode pembayaran --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="payment_method" class="form-label">
                            Metode Pembayaran
                        </label>

                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="">
                                Semua Metode
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

                    {{-- Tombol --}}
                    <div class="col-12 col-xl-9 d-flex align-items-end gap-2">

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>
                            Terapkan Filter
                        </button>

                        <a href="{{ url()->current() }}" class="btn btn-secondary">
                            <i class="fas fa-undo me-1"></i>
                            Reset
                        </a>

                    </div>

                </div>

            </form>

            @error('end_date')
                <div class="alert alert-danger mt-3 mb-0 py-2">
                    {{ $message }}
                </div>
            @enderror

        </div>

        {{-- Informasi jumlah data --}}
        <div class="card-body py-2 border-bottom">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <small class="text-muted">
                    Menampilkan
                    <strong>{{ $sales->firstItem() ?? 0 }}</strong>
                    sampai
                    <strong>{{ $sales->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $sales->total() }}</strong>
                    transaksi.
                </small>

                @if (request()->query())
                    <span class="badge bg-info">
                        Filter aktif
                    </span>
                @endif

            </div>

        </div>

        {{-- Tabel --}}
        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover table-sm align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Bayar</th>
                            <th class="text-end">Kembalian</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($sales as $sale)
                            <tr>

                                {{-- Invoice --}}
                                <td>
                                    <a href="{{ route('admin.sales.receipt', $sale) }}"
                                        class="fw-bold text-decoration-none">
                                        {{ $sale->invoice_number }}
                                    </a>
                                </td>

                                {{-- Kasir --}}
                                <td>
                                    {{ $sale->soldBy?->name ?? 'User tidak ditemukan' }}
                                </td>

                                {{-- Total --}}
                                <td class="text-end text-nowrap">
                                    Rp {{ number_format($sale->total, 0, ',', '.') }}
                                </td>

                                {{-- Bayar --}}
                                <td class="text-end text-nowrap">
                                    Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}
                                </td>

                                {{-- Kembalian --}}
                                <td class="text-end text-nowrap">
                                    Rp {{ number_format($sale->change_amount, 0, ',', '.') }}
                                </td>

                                {{-- Metode pembayaran --}}
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
                                            <span class="badge bg-warning text-dark">
                                                Split
                                            </span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary">
                                                {{ strtoupper($sale->payment_method ?? '-') }}
                                            </span>
                                    @endswitch
                                </td>

                                {{-- Status --}}
                                <td>
                                    @switch($sale->status)
                                        @case('completed')
                                            <span class="badge bg-success">
                                                Completed
                                            </span>
                                        @break

                                        @case('void')
                                            <span class="badge bg-danger">
                                                Void
                                            </span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($sale->status ?? 'Unknown') }}
                                            </span>
                                    @endswitch
                                </td>

                                {{-- Tanggal --}}
                                <td class="text-nowrap">
                                    {{ $sale->created_at?->format('d M Y H:i') ?? '-' }}
                                </td>

                            </tr>

                            @empty

                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="fas fa-search fa-2x mb-3 d-block"></i>

                                        @if (request()->query())
                                            Tidak ada transaksi yang sesuai dengan filter.
                                        @else
                                            Belum ada transaksi penjualan.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            {{-- Pagination --}}
            @if ($sales->hasPages())
                <div class="card-footer">
                    {{ $sales->links() }}
                </div>
            @endif

        </div>

    @endsection
