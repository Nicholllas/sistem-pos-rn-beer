@extends('layouts.admin')

@section('title', 'Cash Ledger')
@section('page-title', 'Cash Ledger')
@section('page-subtitle', 'Pantau arus kas dan telusuri seluruh transaksi keuangan dari satu halaman.')

@section('page-actions')
    <a href="{{ route('admin.finance.transactions.create') }}" class="btn btn-primary">
        Tambah Transaksi
    </a>
@endsection

@section('content')
    @include('admin.finance.partials.alerts')

    @php
        $filterKeys = ['search', 'start_date', 'end_date', 'type', 'status', 'account_id', 'category_id'];
        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();
        $hasActiveFilters = $activeFilterCount > 0;
        $netCashFlow = (float) $summary['income'] - (float) $summary['expense'];

        $selectedAccount = request()->filled('account_id')
            ? $accounts->firstWhere('id', (int) request('account_id'))
            : null;

        $selectedCategory = request()->filled('category_id')
            ? $categories->firstWhere('id', (int) request('category_id'))
            : null;

        $transactionTypeLabels = [
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
        ];

        $transactionStatusLabels = [
            'posted' => 'Posted',
            'void' => 'Void',
        ];
    @endphp

    <div class="crud-page finance-page">
        <div class="finance-summary-grid">
            <article class="finance-summary-card is-income">
                <span>Total Pemasukan</span>

                <strong>
                    Rp {{ number_format($summary['income'], 0, ',', '.') }}
                </strong>

                <small>
                    Akumulasi transaksi posted sesuai filter.
                </small>
            </article>

            <article class="finance-summary-card is-expense">
                <span>Total Pengeluaran</span>

                <strong>
                    Rp {{ number_format($summary['expense'], 0, ',', '.') }}
                </strong>

                <small>
                    Akumulasi transaksi posted sesuai filter.
                </small>
            </article>

            <article class="finance-summary-card">
                <span>Arus Kas Bersih</span>

                <strong @class(['is-danger' => $netCashFlow < 0])>
                    Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                </strong>

                <small>
                    Transfer antar akun tidak memengaruhi total kas keseluruhan.
                </small>
            </article>
        </div>

        <section class="crud-panel finance-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Transaksi</h2>

                    <p>
                        Gunakan satu atau beberapa filter untuk menelusuri arus kas.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.finance.transactions.index') }}" method="GET">
                <div class="finance-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">
                                Pencarian
                            </label>

                            <input type="search" name="search" id="search" class="form-control crud-form-control"
                                value="{{ request('search') }}" placeholder="Nomor transaksi, referensi, atau keterangan..."
                                autocomplete="off">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="start_date" class="form-label">
                                Tanggal Awal
                            </label>

                            <input type="date" name="start_date" id="start_date" class="form-control crud-form-control"
                                value="{{ request('start_date') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="end_date" class="form-label">
                                Tanggal Akhir
                            </label>

                            <input type="date" name="end_date" id="end_date" class="form-control crud-form-control"
                                value="{{ request('end_date') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="type" class="form-label">
                                Jenis
                            </label>

                            <select name="type" id="type" class="form-select crud-form-control">
                                <option value="">Semua jenis</option>
                                <option value="income" @selected(request('type') === 'income')>Pemasukan</option>
                                <option value="expense" @selected(request('type') === 'expense')>Pengeluaran</option>
                                <option value="transfer" @selected(request('type') === 'transfer')>Transfer</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="status" class="form-label">
                                Status
                            </label>

                            <select name="status" id="status" class="form-select crud-form-control">
                                <option value="">Semua status</option>
                                <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                                <option value="void" @selected(request('status') === 'void')>Void</option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="account_id" class="form-label">
                                Akun
                            </label>

                            <select name="account_id" id="account_id" class="form-select crud-form-control">
                                <option value="">Semua akun</option>

                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="category_id" class="form-label">
                                Kategori
                            </label>

                            <select name="category_id" id="category_id" class="form-select crud-form-control">
                                <option value="">Semua kategori</option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                        {{ $category->name }}
                                        ({{ $category->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4 finance-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ route('admin.finance.transactions.index') }}"
                                    class="btn btn-outline-secondary">
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

                    @if (request()->filled('start_date'))
                        <span class="finance-filter-chip">
                            Mulai: {{ request('start_date') }}
                        </span>
                    @endif

                    @if (request()->filled('end_date'))
                        <span class="finance-filter-chip">
                            Sampai: {{ request('end_date') }}
                        </span>
                    @endif

                    @if (request()->filled('type'))
                        <span class="finance-filter-chip">
                            Jenis: {{ $transactionTypeLabels[request('type')] ?? request('type') }}
                        </span>
                    @endif

                    @if (request()->filled('status'))
                        <span class="finance-filter-chip">
                            Status: {{ $transactionStatusLabels[request('status')] ?? request('status') }}
                        </span>
                    @endif

                    @if (request()->filled('account_id'))
                        <span class="finance-filter-chip">
                            Akun: {{ $selectedAccount?->name ?? request('account_id') }}
                        </span>
                    @endif

                    @if (request()->filled('category_id'))
                        <span class="finance-filter-chip">
                            Kategori: {{ $selectedCategory?->name ?? request('category_id') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        <section class="crud-panel finance-result-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Riwayat Transaksi Finance</h2>

                    <p>
                        @if ($hasActiveFilters)
                            Transaksi yang sesuai dengan filter yang diterapkan.
                        @else
                            Seluruh transaksi Finance terbaru.
                        @endif
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($transactions->total(), 0, ',', '.') }}
                    transaksi
                </span>
            </div>

            <div class="finance-result-summary">
                <span>
                    Menampilkan
                    <strong>{{ $transactions->firstItem() ?? 0 }}</strong>
                    sampai
                    <strong>{{ $transactions->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $transactions->total() }}</strong>
                    transaksi
                </span>

                @if ($transactions->hasPages())
                    <span>
                        Halaman {{ $transactions->currentPage() }}
                        dari {{ $transactions->lastPage() }}
                    </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table crud-table finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Transaksi</th>
                            <th>Jenis</th>
                            <th>Akun</th>
                            <th>Kategori/Keterangan</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr @class(['is-void' => $transaction->status === 'void'])>
                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                        </strong>

                                        <small>
                                            {{ $transaction->transaction_date->format('H:i') }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="finance-transaction-cell">
                                        <strong>{{ $transaction->transaction_number }}</strong>

                                        @if ($transaction->reference_number)
                                            <span>Ref. {{ $transaction->reference_number }}</span>
                                        @endif

                                        <small>
                                            {{ $transaction->creator?->name ?? 'User tidak ditemukan' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span @class([
                                        'finance-type-badge',
                                        'is-income' => $transaction->type === 'income',
                                        'is-expense' => $transaction->type === 'expense',
                                        'is-transfer' => $transaction->type === 'transfer',
                                    ])>
                                        {{ $transactionTypeLabels[$transaction->type] ?? ucfirst($transaction->type) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="finance-account-cell">
                                        <strong>
                                            {{ $transaction->account?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            @if ($transaction->type === 'transfer')
                                                Ke {{ $transaction->transferAccount?->name ?? '-' }}
                                            @else
                                                {{ $transaction->account?->code ?? 'Akun tidak tersedia' }}
                                            @endif
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="finance-description-cell">
                                        <strong>
                                            @if ($transaction->category)
                                                {{ $transaction->category->name }}
                                            @elseif ($transaction->type === 'transfer')
                                                Transfer Antar Akun
                                            @else
                                                -
                                            @endif
                                        </strong>

                                        @if ($transaction->description)
                                            <small>
                                                {{ \Illuminate\Support\Str::limit($transaction->description, 60) }}
                                            </small>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="finance-money-cell">
                                        <strong @class([
                                            'is-income' => $transaction->type === 'income',
                                            'is-expense' => $transaction->type === 'expense',
                                        ])>
                                            @if ($transaction->type === 'income')
                                                +
                                            @elseif ($transaction->type === 'expense')
                                                -
                                            @endif
                                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            {{ $transaction->status === 'void' ? 'tidak memengaruhi saldo' : 'transaksi posted' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span @class([
                                        'crud-status',
                                        'is-success' => $transaction->status === 'posted',
                                        'is-danger' => $transaction->status === 'void',
                                    ])>
                                        {{ $transaction->status === 'posted' ? 'Posted' : 'Void' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.finance.transactions.show', $transaction) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crud-empty-state">
                                        @if ($hasActiveFilters)
                                            <strong>Transaksi tidak ditemukan</strong>

                                            <p>
                                                Tidak ada transaksi yang sesuai dengan kombinasi filter tersebut.
                                            </p>

                                            <a href="{{ route('admin.finance.transactions.index') }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Hapus Filter
                                            </a>
                                        @else
                                            <strong>Belum ada transaksi finance</strong>

                                            <p>
                                                Catat pemasukan, pengeluaran, atau transfer antar akun pertama.
                                            </p>

                                            <a href="{{ route('admin.finance.transactions.create') }}"
                                                class="btn btn-primary btn-sm">
                                                Tambah Transaksi
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $transactions->firstItem() }}
                        sampai {{ $transactions->lastItem() }}
                        dari {{ $transactions->total() }} transaksi
                    </span>

                    {{ $transactions->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
