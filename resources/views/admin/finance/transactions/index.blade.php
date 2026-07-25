@extends('layouts.admin')

@section('title', 'Cash Ledger')
@section('page-title', 'Cash Ledger')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">
                        Total Pemasukan
                    </div>

                    <div class="fs-4 fw-bold text-success">
                        Rp{{ number_format($summary['income'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">
                        Total Pengeluaran
                    </div>

                    <div class="fs-4 fw-bold text-danger">
                        Rp{{ number_format($summary['expense'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">
                        Selisih Pemasukan dan Pengeluaran
                    </div>

                    @php
                        $netCashFlow = (float) $summary['income'] - (float) $summary['expense'];
                    @endphp

                    <div class="fs-4 fw-bold {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp{{ number_format($netCashFlow, 0, ',', '.') }}
                    </div>

                    <div class="small text-muted mt-1">
                        Transfer antar akun tidak memengaruhi total kas keseluruhan.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Filter Transaksi
            </h3>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.finance.transactions.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">
                            Pencarian
                        </label>

                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ request('search') }}" placeholder="Nomor transaksi, referensi, atau keterangan">
                    </div>

                    <div class="col-md-2">
                        <label for="start_date" class="form-label">
                            Tanggal Awal
                        </label>

                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ request('start_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="end_date" class="form-label">
                            Tanggal Akhir
                        </label>

                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ request('end_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="type" class="form-label">
                            Jenis
                        </label>

                        <select name="type" id="type" class="form-select">
                            <option value="">Semua jenis</option>
                            <option value="income" @selected(request('type') === 'income')>
                                Pemasukan
                            </option>
                            <option value="expense" @selected(request('type') === 'expense')>
                                Pengeluaran
                            </option>
                            <option value="transfer" @selected(request('type') === 'transfer')>
                                Transfer
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="posted" @selected(request('status') === 'posted')>
                                Posted
                            </option>
                            <option value="void" @selected(request('status') === 'void')>
                                Void
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="account_id" class="form-label">
                            Akun
                        </label>

                        <select name="account_id" id="account_id" class="form-select">
                            <option value="">Semua akun</option>

                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="category_id" class="form-label">
                            Kategori
                        </label>

                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Semua kategori</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                    ({{ $category->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Terapkan Filter
                        </button>

                        <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                Riwayat Transaksi Finance
            </h3>

            <a href="{{ route('admin.finance.transactions.create') }}" class="btn btn-primary btn-sm">
                Tambah Transaksi
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nomor Transaksi</th>
                            <th>Jenis</th>
                            <th>Akun</th>
                            <th>Kategori/Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Status</th>
                            <th width="90">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr class="{{ $transaction->status === 'void' ? 'table-light text-muted' : '' }}">
                                <td>
                                    {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $transaction->transaction_number }}
                                    </div>

                                    @if ($transaction->reference_number)
                                        <div class="small text-muted">
                                            Ref: {{ $transaction->reference_number }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if ($transaction->type === 'income')
                                        <span class="badge bg-success">
                                            Pemasukan
                                        </span>
                                    @elseif ($transaction->type === 'expense')
                                        <span class="badge bg-danger">
                                            Pengeluaran
                                        </span>
                                    @else
                                        <span class="badge bg-primary">
                                            Transfer
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($transaction->type === 'transfer')
                                        <div>
                                            {{ $transaction->account?->name ?? '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            Ke {{ $transaction->transferAccount?->name ?? '-' }}
                                        </div>
                                    @else
                                        {{ $transaction->account?->name ?? '-' }}
                                    @endif
                                </td>

                                <td>
                                    @if ($transaction->category)
                                        <div>
                                            {{ $transaction->category->name }}
                                        </div>
                                    @elseif ($transaction->type === 'transfer')
                                        <div>Transfer Antar Akun</div>
                                    @else
                                        <div>-</div>
                                    @endif

                                    @if ($transaction->description)
                                        <div class="small text-muted">
                                            {{ \Illuminate\Support\Str::limit($transaction->description, 60) }}
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end fw-semibold">
                                    @if ($transaction->type === 'income')
                                        <span class="text-success">
                                            + Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @elseif ($transaction->type === 'expense')
                                        <span class="text-danger">
                                            - Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span>
                                            Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if ($transaction->status === 'posted')
                                        <span class="badge bg-success">
                                            Posted
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Void
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('admin.finance.transactions.show', $transaction) }}"
                                        class="btn btn-info btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Belum ada transaksi finance.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($transactions->hasPages())
            <div class="card-footer">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

@endsection
