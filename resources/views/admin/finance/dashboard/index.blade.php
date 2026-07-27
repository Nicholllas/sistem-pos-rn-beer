@extends('layouts.admin')

@section('title', 'Dashboard Finance')
@section('page-title', 'Dashboard Finance')
@section('page-subtitle', 'Pantau posisi saldo, arus kas, pengeluaran, dan kesehatan integrasi Finance.')

@section('page-actions')
    <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-outline-secondary">
        Cash Ledger
    </a>

    <a href="{{ route('admin.finance.reconciliation.index') }}" class="btn btn-primary">
        Rekonsiliasi
    </a>
@endsection

@section('content')
    @php
        $accountTypeLabels = [
            'cash' => 'Kas Tunai',
            'bank' => 'Bank',
            'qris' => 'QRIS',
            'ewallet' => 'E-Wallet',
            'other' => 'Lainnya',
        ];

        $transactionTypeLabels = [
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
        ];

        $maxDailyValue = max(
            1,
            (float) $dailyCashFlow->max('income'),
            (float) $dailyCashFlow->max('expense')
        );

        $maxExpenseCategory = max(
            1,
            (float) $expenseCategories->max('total')
        );

        $periodLabel =
            $startDate->format('d M Y')
            .' – '
            .$endDate->format('d M Y');

        $reconciliationHealthy =
            $reconciliationIssueCount === 0;
    @endphp

    <div class="crud-page finance-page finance-dashboard-page">
        <section class="crud-panel finance-dashboard-filter">
            <form action="{{ route('admin.finance.dashboard') }}" method="GET">
                <div class="finance-dashboard-filter-row">
                    <div>
                        <strong>Periode Dashboard</strong>
                        <span>{{ $periodLabel }}</span>
                    </div>

                    <div class="finance-dashboard-filter-inputs">
                        <div>
                            <label for="start_date" class="form-label">
                                Tanggal Awal
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="form-control crud-form-control"
                                value="{{ $startDate->toDateString() }}"
                            >
                        </div>

                        <div>
                            <label for="end_date" class="form-label">
                                Tanggal Akhir
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="form-control crud-form-control"
                                value="{{ $endDate->toDateString() }}"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Terapkan
                        </button>

                        <a
                            href="{{ route('admin.finance.dashboard') }}"
                            class="btn btn-outline-secondary"
                        >
                            Bulan Ini
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <div class="finance-summary-grid finance-dashboard-summary">
            <article class="finance-summary-card">
                <span>Total Saldo</span>

                <strong @class([
                    'is-danger' => $totalBalance < 0,
                ])>
                    Rp {{ number_format($totalBalance, 0, ',', '.') }}
                </strong>

                <small>
                    Saldo seluruh akun Finance.
                </small>
            </article>

            <article class="finance-summary-card is-income">
                <span>Pemasukan Periode</span>

                <strong>
                    Rp {{ number_format($periodSummary['income'], 0, ',', '.') }}
                </strong>

                <small>
                    Pemasukan posted pada periode terpilih.
                </small>
            </article>

            <article class="finance-summary-card is-expense">
                <span>Pengeluaran Periode</span>

                <strong>
                    Rp {{ number_format($periodSummary['expense'], 0, ',', '.') }}
                </strong>

                <small>
                    Pengeluaran posted pada periode terpilih.
                </small>
            </article>

            <article class="finance-summary-card">
                <span>Arus Kas Bersih</span>

                <strong @class([
                    'is-danger' =>
                        $periodSummary['net_cash_flow'] < 0,
                ])>
                    Rp {{ number_format(
                        $periodSummary['net_cash_flow'],
                        0,
                        ',',
                        '.'
                    ) }}
                </strong>

                <small>
                    Pemasukan dikurangi pengeluaran.
                </small>
            </article>
        </div>

        <section @class([
            'finance-health-panel',
            'is-healthy' => $reconciliationHealthy,
            'has-issues' => ! $reconciliationHealthy,
        ])>
            <div>
                <span>Status Integrasi Finance</span>

                <strong>
                    {{ $reconciliationHealthy
                        ? 'Sehat'
                        : 'Perlu Pemeriksaan' }}
                </strong>

                <p>
                    @if ($reconciliationHealthy)
                        Seluruh sumber transaksi pada periode ini sudah sesuai dengan posting Finance.
                    @else
                        Terdapat {{ $reconciliationIssueCount }} transaksi yang perlu diperiksa melalui rekonsiliasi.
                    @endif
                </p>
            </div>

            <div class="finance-health-stats">
                <span>
                    <strong>{{ $reconciliationSummary['matched'] }}</strong>
                    Sesuai
                </span>

                <span>
                    <strong>{{ $reconciliationSummary['unposted'] }}</strong>
                    Belum terposting
                </span>

                <span>
                    <strong>{{ $reconciliationSummary['amount_mismatch'] }}</strong>
                    Selisih nominal
                </span>

                <span>
                    <strong>{{ $reconciliationSummary['status_mismatch'] }}</strong>
                    Selisih status
                </span>
            </div>

            <a
                href="{{ route('admin.finance.reconciliation.index', [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ]) }}"
                class="btn btn-sm {{ $reconciliationHealthy
                    ? 'btn-outline-success'
                    : 'btn-outline-danger' }}"
            >
                Buka Rekonsiliasi
            </a>
        </section>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Saldo per Akun</h2>

                    <p>
                        Posisi saldo berdasarkan seluruh transaksi posted.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $accountBalances->count() }} akun
                </span>
            </div>

            <div class="finance-account-grid">
                @forelse ($accountBalances as $account)
                    <article @class([
                        'finance-account-card',
                        'is-inactive' => ! $account['is_active'],
                    ])>
                        <div class="finance-account-card-header">
                            <div>
                                <span>{{ $account['code'] }}</span>
                                <h3>{{ $account['name'] }}</h3>
                            </div>

                            <span class="finance-account-type">
                                {{ $accountTypeLabels[$account['type']]
                                    ?? ucfirst($account['type']) }}
                            </span>
                        </div>

                        <div class="finance-account-balance">
                            <span>Saldo Saat Ini</span>

                            <strong @class([
                                'is-danger' =>
                                    $account['current_balance'] < 0,
                            ])>
                                Rp {{ number_format(
                                    $account['current_balance'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </strong>
                        </div>

                        <dl class="finance-account-breakdown">
                            <div>
                                <dt>Saldo awal</dt>
                                <dd>
                                    Rp {{ number_format(
                                        $account['opening_balance'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </dd>
                            </div>

                            <div>
                                <dt>Pemasukan</dt>
                                <dd class="is-income">
                                    + Rp {{ number_format(
                                        $account['income'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </dd>
                            </div>

                            <div>
                                <dt>Pengeluaran</dt>
                                <dd class="is-expense">
                                    - Rp {{ number_format(
                                        $account['expense'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </dd>
                            </div>

                            <div>
                                <dt>Transfer masuk</dt>
                                <dd>
                                    + Rp {{ number_format(
                                        $account['incoming_transfer'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </dd>
                            </div>

                            <div>
                                <dt>Transfer keluar</dt>
                                <dd>
                                    - Rp {{ number_format(
                                        $account['outgoing_transfer'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </dd>
                            </div>
                        </dl>

                        @if (! $account['is_active'])
                            <span class="crud-status is-danger">
                                Nonaktif
                            </span>
                        @endif
                    </article>
                @empty
                    <div class="crud-empty-state">
                        <strong>Belum ada akun Finance</strong>

                        <p>
                            Tambahkan akun kas, bank, atau QRIS untuk mulai memantau saldo.
                        </p>

                        <a
                            href="{{ route('admin.finance.accounts.create') }}"
                            class="btn btn-primary btn-sm"
                        >
                            Tambah Akun
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        <div class="finance-dashboard-two-column">
            <section class="crud-panel">
                <div class="crud-panel-header">
                    <div>
                        <h2>Arus Kas Harian</h2>

                        <p>
                            Perbandingan pemasukan dan pengeluaran per hari.
                        </p>
                    </div>
                </div>

                <div class="finance-daily-flow">
                    @forelse ($dailyCashFlow as $row)
                        @php
                            $incomeWidth =
                                ($row['income'] / $maxDailyValue) * 100;

                            $expenseWidth =
                                ($row['expense'] / $maxDailyValue) * 100;
                        @endphp

                        <article class="finance-daily-flow-row">
                            <span class="finance-daily-flow-date">
                                {{ $row['label'] }}
                            </span>

                            <div class="finance-daily-flow-bars">
                                <div>
                                    <span>Pemasukan</span>

                                    <div class="finance-flow-track">
                                        <span
                                            class="is-income"
                                            style="width: {{ $incomeWidth }}%"
                                        ></span>
                                    </div>

                                    <strong>
                                        Rp {{ number_format(
                                            $row['income'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>
                                </div>

                                <div>
                                    <span>Pengeluaran</span>

                                    <div class="finance-flow-track">
                                        <span
                                            class="is-expense"
                                            style="width: {{ $expenseWidth }}%"
                                        ></span>
                                    </div>

                                    <strong>
                                        Rp {{ number_format(
                                            $row['expense'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="crud-empty-state">
                            <strong>Belum ada arus kas</strong>
                            <p>Tidak ada transaksi pada periode ini.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="crud-panel">
                <div class="crud-panel-header">
                    <div>
                        <h2>Pengeluaran Terbesar</h2>

                        <p>
                            Kategori dengan arus kas keluar tertinggi.
                        </p>
                    </div>
                </div>

                <div class="finance-expense-ranking">
                    @forelse ($expenseCategories as $index => $category)
                        @php
                            $width =
                                ($category['total'] / $maxExpenseCategory)
                                * 100;
                        @endphp

                        <article>
                            <span class="finance-expense-rank">
                                {{ $index + 1 }}
                            </span>

                            <div>
                                <div class="finance-expense-label">
                                    <strong>
                                        {{ $category['category_name'] }}
                                    </strong>

                                    <span>
                                        Rp {{ number_format(
                                            $category['total'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>
                                </div>

                                <div class="finance-expense-track">
                                    <span style="width: {{ $width }}%"></span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="crud-empty-state">
                            <strong>Belum ada pengeluaran</strong>
                            <p>Tidak ada transaksi pengeluaran pada periode ini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Aktivitas Finance Terbaru</h2>

                    <p>
                        Sepuluh transaksi terakhir pada periode terpilih.
                    </p>
                </div>

                <a
                    href="{{ route('admin.finance.transactions.index', [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ]) }}"
                    class="btn btn-sm btn-outline-primary"
                >
                    Lihat Semua
                </a>
            </div>

            <div class="table-responsive">
                <table class="table crud-table finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Transaksi</th>
                            <th>Jenis</th>
                            <th>Akun</th>
                            <th>Kategori</th>
                            <th>Nominal</th>
                            <th>Sumber</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($latestTransactions as $transaction)
                            @php
                                $sourceLabel = $transaction->isSystemGenerated()
                                    ? match (class_basename(
                                        $transaction->reference_type
                                    )) {
                                        'Sale' => 'Penjualan',
                                        'Purchase' => 'Pembelian',
                                        default => 'Sistem',
                                    }
                                    : 'Manual';
                            @endphp

                            <tr @class([
                                'is-void' =>
                                    $transaction->status === 'void',
                            ])>
                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $transaction->transaction_date
                                                ->format('d M Y') }}
                                        </strong>

                                        <small>
                                            {{ $transaction->transaction_date
                                                ->format('H:i') }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <strong>
                                        {{ $transaction->transaction_number }}
                                    </strong>
                                </td>

                                <td>
                                    <span @class([
                                        'finance-type-badge',
                                        'is-income' =>
                                            $transaction->type === 'income',
                                        'is-expense' =>
                                            $transaction->type === 'expense',
                                        'is-transfer' =>
                                            $transaction->type === 'transfer',
                                    ])>
                                        {{ $transactionTypeLabels[
                                            $transaction->type
                                        ] ?? ucfirst($transaction->type) }}
                                    </span>
                                </td>

                                <td>
                                    {{ $transaction->account?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $transaction->category?->name
                                        ?? (
                                            $transaction->type === 'transfer'
                                                ? 'Transfer Antar Akun'
                                                : '-'
                                        ) }}
                                </td>

                                <td>
                                    <strong @class([
                                        'is-income' =>
                                            $transaction->type === 'income',
                                        'is-expense' =>
                                            $transaction->type === 'expense',
                                    ])>
                                        Rp {{ number_format(
                                            $transaction->amount,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $sourceLabel }}
                                    </span>
                                </td>

                                <td>
                                    <span @class([
                                        'crud-status',
                                        'is-success' =>
                                            $transaction->status === 'posted',
                                        'is-danger' =>
                                            $transaction->status === 'void',
                                    ])>
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a
                                            href="{{ route(
                                                'admin.finance.transactions.show',
                                                $transaction
                                            ) }}"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="crud-empty-state">
                                        <strong>Belum ada aktivitas Finance</strong>
                                        <p>Tidak ada transaksi pada periode ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
