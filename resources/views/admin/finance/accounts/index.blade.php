@extends('layouts.admin')

@section('title', 'Akun Finance')
@section('page-title', 'Akun Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Filter Akun</h3>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.finance.accounts.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="search" class="form-label">
                            Pencarian
                        </label>

                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ request('search') }}" placeholder="Cari kode atau nama akun">
                    </div>

                    <div class="col-md-3">
                        <label for="type" class="form-label">
                            Jenis Akun
                        </label>

                        <select name="type" id="type" class="form-select">
                            <option value="">Semua jenis</option>
                            <option value="cash" @selected(request('type') === 'cash')>
                                Kas Tunai
                            </option>
                            <option value="bank" @selected(request('type') === 'bank')>
                                Rekening Bank
                            </option>
                            <option value="qris" @selected(request('type') === 'qris')>
                                Saldo QRIS
                            </option>
                            <option value="ewallet" @selected(request('type') === 'ewallet')>
                                E-Wallet
                            </option>
                            <option value="other" @selected(request('type') === 'other')>
                                Lainnya
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="active" @selected(request('status') === 'active')>
                                Aktif
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')>
                                Nonaktif
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Filter
                        </button>

                        <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-secondary">
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
                Daftar Akun Kas dan Bank
            </h3>

            <a href="{{ route('admin.finance.accounts.create') }}" class="btn btn-primary btn-sm">
                Tambah Akun
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Akun</th>
                            <th>Jenis</th>
                            <th class="text-end">Saldo Awal</th>
                            <th class="text-end">Saldo Saat Ini</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-center">Status</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($accounts as $account)
                            @php
                                $typeLabel = match ($account->type) {
                                    'cash' => 'Kas Tunai',
                                    'bank' => 'Bank',
                                    'qris' => 'QRIS',
                                    'ewallet' => 'E-Wallet',
                                    default => 'Lainnya',
                                };

                                $typeClass = match ($account->type) {
                                    'cash' => 'bg-success',
                                    'bank' => 'bg-primary',
                                    'qris' => 'bg-info text-dark',
                                    'ewallet' => 'bg-warning text-dark',
                                    default => 'bg-secondary',
                                };

                                $transactionCount = $account->transactions_count + $account->incoming_transfers_count;
                            @endphp

                            <tr>
                                <td>
                                    <span class="fw-semibold">
                                        {{ $account->code }}
                                    </span>
                                </td>

                                <td>{{ $account->name }}</td>

                                <td>
                                    <span class="badge {{ $typeClass }}">
                                        {{ $typeLabel }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    Rp{{ number_format($account->opening_balance, 0, ',', '.') }}
                                </td>

                                <td class="text-end fw-semibold">
                                    Rp{{ number_format($account->current_balance, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    {{ number_format($transactionCount) }}
                                </td>

                                <td class="text-center">
                                    @if ($account->is_active)
                                        <span class="badge bg-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.finance.accounts.edit', $account) }}"
                                            class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.finance.accounts.destroy', $account) }}"
                                            method="POST" onsubmit="return confirm('Hapus akun finance ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Belum ada akun finance.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($accounts->hasPages())
            <div class="card-footer">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>

@endsection
