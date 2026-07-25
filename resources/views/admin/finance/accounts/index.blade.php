@extends('layouts.admin')

@section('title', 'Akun Kas & Bank')
@section('page-title', 'Akun Kas & Bank')
@section('page-subtitle', 'Kelola akun kas, bank, QRIS, dan dompet digital yang digunakan pada transaksi.')

@section('page-actions')
    <a href="{{ route('admin.finance.accounts.create') }}" class="btn btn-primary">
        Tambah Akun
    </a>
@endsection

@section('content')
    @include('admin.finance.partials.alerts')

    @php
        $filterKeys = ['search', 'type', 'status'];
        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();
        $hasActiveFilters = $activeFilterCount > 0;

        $accountTypeLabels = [
            'cash' => 'Kas Tunai',
            'bank' => 'Rekening Bank',
            'qris' => 'Saldo QRIS',
            'ewallet' => 'E-Wallet',
            'other' => 'Lainnya',
        ];

        $accountStatusLabels = [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ];
    @endphp

    <div class="crud-page finance-page">
        <section class="crud-panel finance-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Akun</h2>

                    <p>
                        Cari akun berdasarkan kode, nama, jenis, atau statusnya.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.finance.accounts.index') }}" method="GET">
                <div class="finance-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">
                                Pencarian
                            </label>

                            <input type="search" name="search" id="search" class="form-control crud-form-control"
                                value="{{ request('search') }}" placeholder="Cari kode atau nama akun..."
                                autocomplete="off">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="type" class="form-label">
                                Jenis Akun
                            </label>

                            <select name="type" id="type" class="form-select crud-form-control">
                                <option value="">Semua jenis</option>

                                @foreach ($accountTypeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(request('type') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="status" class="form-label">
                                Status
                            </label>

                            <select name="status" id="status" class="form-select crud-form-control">
                                <option value="">Semua status</option>
                                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-3 finance-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-outline-secondary">
                                    Hapus Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="finance-active-filters">
                    <span class="finance-active-filter-label">
                        Filter:
                    </span>

                    @if (request()->filled('search'))
                        <span class="finance-filter-chip">
                            Pencarian: {{ request('search') }}
                        </span>
                    @endif

                    @if (request()->filled('type'))
                        <span class="finance-filter-chip">
                            Jenis: {{ $accountTypeLabels[request('type')] ?? request('type') }}
                        </span>
                    @endif

                    @if (request()->filled('status'))
                        <span class="finance-filter-chip">
                            Status: {{ $accountStatusLabels[request('status')] ?? request('status') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        <section class="crud-panel finance-result-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Akun Kas dan Bank</h2>

                    <p>
                        @if ($hasActiveFilters)
                            Akun yang sesuai dengan filter yang diterapkan.
                        @else
                            Seluruh akun pembayaran dan penyimpanan dana.
                        @endif
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($accounts->total(), 0, ',', '.') }}
                    akun
                </span>
            </div>

            <div class="finance-result-summary">
                <span>
                    Menampilkan
                    <strong>{{ $accounts->firstItem() ?? 0 }}</strong>
                    sampai
                    <strong>{{ $accounts->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $accounts->total() }}</strong>
                    akun
                </span>

                @if ($accounts->hasPages())
                    <span>
                        Halaman {{ $accounts->currentPage() }}
                        dari {{ $accounts->lastPage() }}
                    </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table crud-table finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Jenis</th>
                            <th>Saldo Awal</th>
                            <th>Saldo Saat Ini</th>
                            <th>Transaksi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($accounts as $account)
                            @php
                                $typeLabel = $accountTypeLabels[$account->type] ?? 'Lainnya';
                                $typeClass = match ($account->type) {
                                    'cash' => 'is-cash',
                                    'bank' => 'is-bank',
                                    'qris' => 'is-qris',
                                    'ewallet' => 'is-ewallet',
                                    default => 'is-other',
                                };

                                $transactionCount = $account->transactions_count + $account->incoming_transfers_count;
                            @endphp

                            <tr>
                                <td>
                                    <div class="crud-record-cell">
                                        <div>
                                            <strong>{{ $account->name }}</strong>
                                            <small class="finance-record-code">{{ $account->code }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="finance-type-badge {{ $typeClass }}">
                                        {{ $typeLabel }}
                                    </span>
                                </td>

                                <td>
                                    <div class="finance-money-cell">
                                        <strong>
                                            Rp {{ number_format($account->opening_balance, 0, ',', '.') }}
                                        </strong>
                                        <small>saldo pembukaan</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="finance-money-cell">
                                        <strong>
                                            Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                                        </strong>
                                        <small>saldo berjalan</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-count-value">
                                        <strong>{{ number_format($transactionCount, 0, ',', '.') }}</strong>
                                        <span>transaksi</span>
                                    </div>
                                </td>

                                <td>
                                    <span @class([
                                        'crud-status',
                                        'is-success' => $account->is_active,
                                        'is-muted' => !$account->is_active,
                                    ])>
                                        {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.finance.accounts.edit', $account) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.finance.accounts.destroy', $account) }}"
                                            method="POST" class="js-confirm-action" data-confirm-icon="warning"
                                            data-confirm-title="Hapus akun?"
                                            data-confirm-message="Akun {{ $account->name }} akan dihapus jika belum memiliki riwayat transaksi."
                                            data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crud-empty-state">
                                        @if ($hasActiveFilters)
                                            <strong>Akun tidak ditemukan</strong>

                                            <p>
                                                Tidak ada akun yang sesuai dengan kombinasi filter tersebut.
                                            </p>

                                            <a href="{{ route('admin.finance.accounts.index') }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Hapus Filter
                                            </a>
                                        @else
                                            <strong>Belum ada akun finance</strong>

                                            <p>
                                                Tambahkan akun kas atau bank untuk mulai mencatat transaksi.
                                            </p>

                                            <a href="{{ route('admin.finance.accounts.create') }}"
                                                class="btn btn-primary btn-sm">
                                                Tambah Akun
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($accounts->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $accounts->firstItem() }}
                        sampai {{ $accounts->lastItem() }}
                        dari {{ $accounts->total() }} akun
                    </span>

                    {{ $accounts->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
