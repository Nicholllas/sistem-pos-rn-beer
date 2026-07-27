@extends('layouts.admin')

@section('title', 'Rekonsiliasi Finance')
@section('page-title', 'Rekonsiliasi Finance')
@section('page-subtitle', 'Periksa kesesuaian transaksi penjualan dan pembelian terhadap posting Cash Ledger.')

@section('page-actions')
    <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-outline-secondary">
        Buka Cash Ledger
    </a>
@endsection

@section('content')
    @php
        $statusLabels = [
            'matched' => 'Sesuai',
            'unposted' => 'Belum Terposting',
            'amount_mismatch' => 'Nominal Tidak Sesuai',
            'status_mismatch' => 'Status Tidak Sesuai',
        ];

        $sourceLabels = [
            'sale' => 'Penjualan',
            'purchase' => 'Pembelian',
        ];

        $filterKeys = [
            'search',
            'source',
            'status',
            'start_date',
            'end_date',
        ];

        $activeFilterCount = collect($filterKeys)
            ->filter(fn ($key) => request()->filled($key))
            ->count();
    @endphp

    <div class="crud-page finance-page">
        <div class="finance-summary-grid">
            <article class="finance-summary-card">
                <span>Total Sumber</span>
                <strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong>
                <small>Penjualan dan pembelian sesuai filter.</small>
            </article>

            <article class="finance-summary-card is-income">
                <span>Sesuai</span>
                <strong>{{ number_format($summary['matched'], 0, ',', '.') }}</strong>
                <small>Nilai dan status Finance sudah konsisten.</small>
            </article>

            <article class="finance-summary-card is-expense">
                <span>Belum Terposting</span>
                <strong>{{ number_format($summary['unposted'], 0, ',', '.') }}</strong>
                <small>Sumber completed belum memiliki transaksi Finance.</small>
            </article>

            <article class="finance-summary-card">
                <span>Selisih Nominal</span>
                <strong>{{ number_format($summary['amount_mismatch'], 0, ',', '.') }}</strong>
                <small>Nominal sumber berbeda dengan Finance posted.</small>
            </article>

            <article class="finance-summary-card">
                <span>Selisih Status</span>
                <strong>{{ number_format($summary['status_mismatch'], 0, ',', '.') }}</strong>
                <small>Status sumber dan Finance tidak sinkron.</small>
            </article>
        </div>

        <section class="crud-panel finance-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Rekonsiliasi</h2>
                    <p>Telusuri transaksi berdasarkan nomor, sumber, status, dan periode.</p>
                </div>

                @if ($activeFilterCount > 0)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.finance.reconciliation.index') }}" method="GET">
                <div class="finance-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">Pencarian</label>
                            <input
                                type="search"
                                name="search"
                                id="search"
                                class="form-control crud-form-control"
                                value="{{ request('search') }}"
                                placeholder="Nomor invoice atau purchase order..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="source" class="form-label">Sumber</label>
                            <select name="source" id="source" class="form-select crud-form-control">
                                <option value="">Semua sumber</option>
                                <option value="sale" @selected(request('source') === 'sale')>
                                    Penjualan
                                </option>
                                <option value="purchase" @selected(request('source') === 'purchase')>
                                    Pembelian
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select crud-form-control">
                                <option value="">Semua status</option>
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="start_date" class="form-label">Tanggal Awal</label>
                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="form-control crud-form-control"
                                value="{{ request('start_date') }}"
                            >
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="end_date" class="form-label">Tanggal Akhir</label>
                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="form-control crud-form-control"
                                value="{{ request('end_date') }}"
                            >
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>

                            @if ($activeFilterCount > 0)
                                <a
                                    href="{{ route('admin.finance.reconciliation.index') }}"
                                    class="btn btn-outline-secondary"
                                >
                                    Hapus Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="crud-panel finance-result-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Hasil Rekonsiliasi</h2>
                    <p>Perbandingan data sumber terhadap transaksi Finance yang aktif.</p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($items->total(), 0, ',', '.') }} data
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Sumber</th>
                            <th>Status Sumber</th>
                            <th class="text-end">Nilai Sumber</th>
                            <th class="text-end">Finance Posted</th>
                            <th class="text-end">Selisih</th>
                            <th>Status Rekonsiliasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $difference = (float) $item->difference_amount;

                                $sourceUrl = $item->source_type === 'sale'
                                    ? route('admin.sales.receipt', $item->source_id)
                                    : route('admin.purchases.show', $item->source_id);

                                $statusClass = match ($item->reconciliation_status) {
                                    'matched' => 'is-success',
                                    'unposted' => 'is-warning',
                                    default => 'is-danger',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>{{ \Carbon\Carbon::parse($item->source_date)->format('d M Y') }}</strong>
                                        <small>{{ \Carbon\Carbon::parse($item->source_date)->format('H:i') }}</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="finance-transaction-cell">
                                        <strong>{{ $item->source_number }}</strong>
                                        <span>{{ $sourceLabels[$item->source_type] ?? $item->source_type }}</span>
                                        <small>{{ (int) $item->transaction_count }} transaksi Finance</small>
                                    </div>
                                </td>

                                <td>
                                    <span @class([
                                        'crud-status',
                                        'is-success' => $item->source_status === 'completed',
                                        'is-danger' => $item->source_status === 'void',
                                    ])>
                                        {{ $item->source_status === 'completed' ? 'Completed' : 'Void' }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <strong>Rp {{ number_format($item->source_amount, 0, ',', '.') }}</strong>
                                </td>

                                <td class="text-end">
                                    <strong>Rp {{ number_format($item->posted_amount, 0, ',', '.') }}</strong>

                                    @if ((int) $item->void_count > 0)
                                        <div class="small text-muted">
                                            {{ (int) $item->void_count }} transaksi void
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <strong @class([
                                        'text-success' => abs($difference) < 0.01,
                                        'text-danger' => abs($difference) >= 0.01,
                                    ])>
                                        {{ $difference > 0 ? '+' : '' }}
                                        Rp {{ number_format($difference, 0, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    <span class="crud-status {{ $statusClass }}">
                                        {{ $statusLabels[$item->reconciliation_status] ?? $item->reconciliation_status }}
                                    </span>

                                    @if (
                                        $item->reconciliation_status === 'status_mismatch'
                                        && $item->source_status === 'completed'
                                    )
                                        <div class="small text-muted mt-1">
                                            Finance sudah memiliki histori, tetapi tidak ada posting aktif.
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="crud-actions justify-content-end">
                                        <a href="{{ $sourceUrl }}" class="btn btn-sm btn-outline-primary">
                                            Lihat Sumber
                                        </a>

                                        @if (
                                            $item->reconciliation_status === 'unposted'
                                            && $item->source_status === 'completed'
                                            && (int) $item->transaction_count === 0
                                        )
                                            <form
                                                action="{{ route('admin.finance.reconciliation.repost', [
                                                    'sourceType' => $item->source_type,
                                                    'sourceId' => $item->source_id,
                                                ]) }}"
                                                method="POST"
                                                class="d-flex gap-2 align-items-center"
                                                onsubmit="return confirm('Posting ulang transaksi ini ke Finance?')"
                                            >
                                                @csrf

                                                @if (
                                                    $item->source_type === 'purchase'
                                                    && ! $item->source_account_id
                                                )
                                                    <select
                                                        name="finance_account_id"
                                                        class="form-select form-select-sm"
                                                        required
                                                    >
                                                        <option value="">Pilih akun</option>
                                                        @foreach ($financeAccounts as $account)
                                                            <option value="{{ $account->id }}">
                                                                {{ $account->code }} - {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    Posting Ulang
                                                </button>
                                            </form>
                                        @elseif (
                                            $item->source_status === 'void'
                                            && (int) $item->posted_count > 0
                                        )
                                            <form
                                                action="{{ route('admin.finance.reconciliation.sync-void', [
                                                    'sourceType' => $item->source_type,
                                                    'sourceId' => $item->source_id,
                                                ]) }}"
                                                method="POST"
                                                onsubmit="return confirm('Sinkronkan status void ke transaksi Finance?')"
                                            >
                                                @csrf
                                                @method('PUT')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Sinkronkan Void
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crud-empty-state">
                                        <strong>Data rekonsiliasi tidak ditemukan</strong>
                                        <p>Tidak ada data yang sesuai dengan filter yang diterapkan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $items->firstItem() }} sampai {{ $items->lastItem() }}
                        dari {{ $items->total() }} data
                    </span>

                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
