@extends('layouts.admin')

@section('title', 'Shift & Rekonsiliasi Kas')
@section('page-title', 'Shift & Rekonsiliasi Kas')
@section('page-subtitle', 'Monitoring riwayat shift kasir, modal awal, penyelesaian selisih ke Cash Ledger, dan laporan penutupan shift.')

@section('page-actions')
    <a href="{{ route('admin.sales.pos') }}" class="btn btn-primary">
        Buka Kasir / POS
    </a>
@endsection

@section('content')
    @php
        $activeFilterCount = collect(['cashier_id', 'status', 'discrepancy_status', 'start_date', 'end_date'])
            ->filter(fn($key) => request()->filled($key))
            ->count();
    @endphp

    <div class="crud-page">
        {{-- Filter Panel --}}
        <section class="crud-panel shifts-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Riwayat Shift</h2>
                    <p>Saring riwayat shift berdasarkan kasir, status shift, status selisih, atau rentang tanggal.</p>
                </div>
                @if ($activeFilterCount > 0)
                    <span class="crud-count-badge">{{ $activeFilterCount }} filter aktif</span>
                @endif
            </div>

            <div class="crud-panel-body">
                <form action="{{ route('admin.shifts.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="cashier_id" class="form-label">Kasir</label>
                            <select name="cashier_id" id="cashier_id" class="form-select crud-form-control">
                                <option value="">Semua Kasir</option>
                                @foreach ($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" @selected(request('cashier_id') == $cashier->id)>
                                        {{ $cashier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="status" class="form-label">Status Shift</label>
                            <select name="status" id="status" class="form-select crud-form-control">
                                <option value="">Semua Status</option>
                                <option value="open" @selected(request('status') === 'open')>Shift Aktif</option>
                                <option value="closed" @selected(request('status') === 'closed')>Ditutup</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="discrepancy_status" class="form-label">Status Rekonsiliasi</label>
                            <select name="discrepancy_status" id="discrepancy_status" class="form-select crud-form-control">
                                <option value="">Semua</option>
                                <option value="pending" @selected(request('discrepancy_status') === 'pending')>Menunggu Penyelesaian</option>
                                <option value="resolved" @selected(request('discrepancy_status') === 'resolved')>Selesai Diselesaikan</option>
                                <option value="none" @selected(request('discrepancy_status') === 'none')>Sesuai (Tanpa Selisih)</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control crud-form-control"
                                value="{{ request('start_date') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="end_date" class="form-label">Tanggal Akhir</label>
                            <input type="date" name="end_date" id="end_date" class="form-control crud-form-control"
                                value="{{ request('end_date') }}">
                        </div>
                    </div>

                    <div class="crud-filter-actions">
                        @if ($activeFilterCount > 0)
                            <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary">
                                Reset Filter
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if ($errors->any())
            <div class="alert alert-danger mb-4" role="alert">
                <strong>Gagal memproses penyelesaian selisih:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Shifts Table --}}
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Shift Kasir</h2>
                    <p>Rekapitulasi modal awal, penerimaan penjualan, dan hasil rekonsiliasi uang fisik per shift.</p>
                </div>
                <span class="crud-count-badge">{{ $shifts->total() }} shift</span>
            </div>

            <div class="sales-result-summary">
                <span>
                    Menampilkan
                    <strong>{{ $shifts->firstItem() ?? 0 }} - {{ $shifts->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $shifts->total() }}</strong>
                    shift tercatat
                </span>
            </div>

            <div class="crud-panel-body is-flush">
                <div class="table-responsive">
                    <table class="table crud-table is-dense table-hover">
                        <thead>
                            <tr>
                                <th>Shift &amp; Kasir</th>
                                <th>Modal &amp; Penjualan</th>
                                <th>Ekspektasi vs Fisik</th>
                                <th>Selisih &amp; Penyelesaian</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shifts as $shift)
                                @php
                                    $discrepancy = $shift->cash_discrepancy !== null ? (float) $shift->cash_discrepancy : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="crud-cell-stack">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <strong class="text-dark" style="font-size: 0.8125rem;">#{{ $shift->id }} · {{ $shift->user?->name ?? 'Kasir Terhapus' }}</strong>
                                                @if ($shift->status === 'open')
                                                    <span class="crud-status is-info">Shift Aktif</span>
                                                @else
                                                    <span class="crud-status is-muted">Ditutup</span>
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size: 0.7rem;">
                                                Buka: {{ $shift->opened_at?->format('d/m/y H:i') ?? '-' }}
                                                ·
                                                @if ($shift->closed_at)
                                                    Tutup: {{ $shift->closed_at->format('d/m/y H:i') }}
                                                @else
                                                    Berlangsung
                                                @endif
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="crud-cell-stack">
                                            <div class="crud-cell-kv">
                                                <span>Modal Awal</span>
                                                <strong>Rp {{ number_format((float) $shift->starting_cash, 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="crud-cell-kv">
                                                <span>Tunai Bersih</span>
                                                <strong>Rp {{ number_format((float) $shift->total_cash_sales, 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="crud-cell-kv">
                                                <span>Non-Tunai</span>
                                                <strong>Rp {{ number_format((float) ($shift->total_qris_sales + $shift->total_transfer_sales), 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="crud-cell-stack">
                                            <div class="crud-cell-kv">
                                                <span>Ekspektasi</span>
                                                <strong>Rp {{ number_format((float) $shift->expected_cash, 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="crud-cell-kv">
                                                <span>Kas Fisik</span>
                                                <strong>
                                                    @if ($shift->actual_cash !== null)
                                                        Rp {{ number_format((float) $shift->actual_cash, 0, ',', '.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="crud-cell-stack">
                                            <div>
                                                @if ($discrepancy !== null)
                                                    @if ($discrepancy == 0)
                                                        <span class="crud-status is-success">Sesuai (Rp 0)</span>
                                                    @elseif ($discrepancy < 0)
                                                        <span class="crud-status is-danger">Kurang (-Rp {{ number_format(abs($discrepancy), 0, ',', '.') }})</span>
                                                    @else
                                                        <span class="crud-status is-warning">Lebih (+Rp {{ number_format($discrepancy, 0, ',', '.') }})</span>
                                                    @endif
                                                @else
                                                    <span class="crud-neutral-badge">Belum Tutup</span>
                                                @endif
                                            </div>
                                            <div>
                                                @if (! $shift->isClosed())
                                                    <small class="text-muted" style="font-size: 0.6875rem;">Menunggu tutup shift</small>
                                                @elseif ($shift->discrepancy_status === 'none')
                                                    <span class="crud-status is-muted">Tidak Perlu Ledger</span>
                                                @elseif ($shift->discrepancy_status === 'pending')
                                                    <span class="crud-status is-warning">Pending Review</span>
                                                @else
                                                    <div class="crud-cell-stack">
                                                        <span class="crud-status is-success">{{ $shift->discrepancyResolutionLabel() }}</span>
                                                        @if ($shift->catchupSale)
                                                            <small style="font-size: 0.6875rem;">
                                                                <a href="{{ route('admin.sales.receipt', $shift->catchupSale) }}" class="text-decoration-none">
                                                                    {{ $shift->catchupSale->invoice_number }}
                                                                </a>
                                                            </small>
                                                        @endif
                                                        @if (auth()->user()->isAdmin() && $shift->financeTransaction)
                                                            <small style="font-size: 0.6875rem;">
                                                                <a href="{{ route('admin.finance.transactions.show', $shift->financeTransaction) }}" class="text-decoration-none">
                                                                    {{ $shift->financeTransaction->transaction_number }}
                                                                </a>
                                                            </small>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if ($shift->status === 'closed')
                                            <div class="crud-actions">
                                                @if (auth()->user()->isAdmin() && $shift->isPendingDiscrepancy())
                                                    <button type="button" class="btn btn-sm btn-outline-warning btn-resolve-discrepancy"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#resolveDiscrepancyModal"
                                                        data-action="{{ route('admin.shifts.resolve-discrepancy', $shift) }}"
                                                        data-catchup-url="{{ (float) $shift->cash_discrepancy > 0 ? route('admin.shifts.catchup-sale.create', $shift) : '' }}"
                                                        data-shift-id="{{ $shift->id }}"
                                                        data-cashier="{{ $shift->user?->name ?? 'Kasir' }}"
                                                        data-discrepancy="{{ (float) $shift->cash_discrepancy }}">
                                                        Selesaikan
                                                    </button>
                                                @endif
                                                <a href="{{ route('admin.shifts.summary', $shift) }}" class="btn btn-sm btn-outline-primary">
                                                    Laporan Shift
                                                </a>
                                            </div>
                                        @else
                                            <div class="crud-actions">
                                                @if ($shift->user_id === auth()->id())
                                                    <a href="{{ route('admin.sales.pos') }}" class="btn btn-sm btn-outline-primary">
                                                        Buka POS
                                                    </a>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-force-close-shift"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#adminCloseShiftModal"
                                                    data-action="{{ route('admin.shifts.close', $shift) }}"
                                                    data-shift-id="{{ $shift->id }}"
                                                    data-cashier="{{ $shift->user?->name ?? 'Kasir' }}"
                                                    data-starting-cash="{{ number_format((float) $shift->starting_cash, 0, ',', '.') }}"
                                                    data-expected-cash="{{ number_format((float) $shift->expected_cash, 0, ',', '.') }}">
                                                    Tutup Shift
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Belum ada riwayat shift yang sesuai dengan filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($shifts->hasPages())
                    <div class="crud-pagination">
                        <span>Halaman {{ $shifts->currentPage() }} dari {{ $shifts->lastPage() }}</span>
                        {{ $shifts->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Modal Penyelesaian Selisih Shift --}}
    <div class="modal fade" id="resolveDiscrepancyModal" tabindex="-1" aria-labelledby="resolveDiscrepancyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="resolveDiscrepancyForm" method="POST" action="#">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="resolveDiscrepancyModalLabel">Penyelesaian Selisih Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3" id="resolveShiftSummaryText">
                            Pilih metode penyelesaian selisih untuk menyinkronkan atau menyelesaikan catatan kas shift.
                        </div>

                        <div class="purchase-process-info mt-0 mb-3 d-none" id="catchupSalePromptBox">
                            <strong>Kelebihan kas berasal dari penjualan lupa input POS?</strong>
                            <p class="small mb-2 mt-1">Catat penjualan susulan agar stok FIFO terpotong, masuk Laporan Penjualan, dan saldo kas sinkron tanpa membuat shift baru menjadi minus.</p>
                            <a href="#" id="btnGoToCatchupSale" class="btn btn-sm btn-primary">
                                Catat Penjualan Susulan
                            </a>
                        </div>

                        <div class="mb-3">
                            <label for="modal_resolution" class="form-label fw-bold">Metode Penyelesaian <span class="crud-required">*</span></label>
                            <select name="resolution" id="modal_resolution" class="form-select crud-form-control" data-no-combobox required>
                                <option value="ledger_adjustment">Sesuaikan ke Cash Ledger (Posting Selisih Finance)</option>
                                <option value="cashier_covered" id="optionCashierCovered">Diganti / Disetor oleh Kasir (Saldo Ledger Tetap)</option>
                                <option value="manual_resolved">Diselesaikan Manual Tanpa Penyesuaian Ledger</option>
                            </select>
                        </div>

                        <div class="mb-3" id="modalAccountWrapper">
                            <label for="modal_finance_account_id" class="form-label fw-bold">Akun Cash Ledger <span class="crud-required">*</span></label>
                            <select name="finance_account_id" id="modal_finance_account_id" class="form-select crud-form-control" data-no-combobox>
                                @foreach ($financeAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->name }} ({{ strtoupper($account->type) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Saldo akun ini akan bertambah/berkurang sesuai nilai selisih kas fisik.</small>
                        </div>

                        <div class="mb-0">
                            <label for="modal_resolution_notes" class="form-label fw-bold">Catatan Penyelesaian <span class="crud-required">*</span></label>
                            <textarea name="resolution_notes" id="modal_resolution_notes" rows="3" class="form-control crud-form-control"
                                placeholder="Tuliskan alasan dan hasil pemeriksaan selisih kas..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold">Simpan Penyelesaian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminCloseShiftModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="adminCloseShiftForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Tutup Paksa Shift Kasir</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3" id="adminCloseShiftSummary"></div>
                        <div class="mb-3">
                            <label for="admin_actual_cash" class="form-label fw-bold">Uang Fisik di Laci (Rp) <span class="crud-required">*</span></label>
                            <input type="number" name="actual_cash" id="admin_actual_cash" class="form-control crud-form-control"
                                min="0" step="1" placeholder="Masukkan total hitungan uang fisik..." required>
                        </div>
                        <div class="mb-0">
                            <label for="admin_closing_notes" class="form-label fw-bold">Catatan Penutupan Shift</label>
                            <textarea name="closing_notes" id="admin_closing_notes" rows="2" class="form-control crud-form-control"
                                placeholder="Contoh: Shift ditutup oleh supervisor/admin karena kasir lupa tutup shift"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger fw-bold">Tutup Shift Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminCloseForm = document.getElementById('adminCloseShiftForm');
            const adminCloseSummary = document.getElementById('adminCloseShiftSummary');
            const adminActualCash = document.getElementById('admin_actual_cash');
            const adminClosingNotes = document.getElementById('admin_closing_notes');

            document.querySelectorAll('.btn-force-close-shift').forEach(function(button) {
                button.addEventListener('click', function() {
                    adminCloseForm.action = button.dataset.action;
                    adminCloseSummary.textContent =
                        `Shift #${button.dataset.shiftId} (${button.dataset.cashier}) — Modal Awal: Rp ${button.dataset.startingCash}, Ekspektasi Kas Sistem: Rp ${button.dataset.expectedCash}.`;
                    adminActualCash.value = '';
                    adminClosingNotes.value = '';
                });
            });

            const modalForm = document.getElementById('resolveDiscrepancyForm');
            const summaryText = document.getElementById('resolveShiftSummaryText');
            const catchupPromptBox = document.getElementById('catchupSalePromptBox');
            const catchupLink = document.getElementById('btnGoToCatchupSale');
            const resolutionSelect = document.getElementById('modal_resolution');
            const optionCashierCovered = document.getElementById('optionCashierCovered');
            const accountWrapper = document.getElementById('modalAccountWrapper');

            const syncAccountVisibility = () => {
                if (!resolutionSelect || !accountWrapper) return;
                accountWrapper.classList.toggle('d-none', resolutionSelect.value !== 'ledger_adjustment');
            };

            resolutionSelect?.addEventListener('change', syncAccountVisibility);

            document.querySelectorAll('.btn-resolve-discrepancy').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const catchupUrl = this.dataset.catchupUrl;
                    const shiftId = this.dataset.shiftId;
                    const cashier = this.dataset.cashier;
                    const discrepancy = parseFloat(this.dataset.discrepancy || '0');
                    const isShortage = discrepancy < 0;
                    const absFormatted = 'Rp ' + Math.abs(discrepancy).toLocaleString('id-ID');

                    if (modalForm) modalForm.action = action;

                    if (catchupPromptBox && catchupLink) {
                        if (!isShortage && catchupUrl) {
                            catchupLink.href = catchupUrl;
                            catchupPromptBox.classList.remove('d-none');
                        } else {
                            catchupLink.href = '#';
                            catchupPromptBox.classList.add('d-none');
                        }
                    }

                    if (summaryText) {
                        summaryText.innerHTML = `
                            Shift <strong>#${shiftId}</strong> (${cashier}) memiliki selisih
                            <strong>${isShortage ? 'KURANG (-' + absFormatted + ')' : 'LEBIH (+' + absFormatted + ')'}</strong>.
                        `;
                    }

                    if (optionCashierCovered) {
                        optionCashierCovered.hidden = !isShortage;
                        optionCashierCovered.disabled = !isShortage;
                    }

                    if (resolutionSelect) {
                        resolutionSelect.value = 'ledger_adjustment';
                    }

                    syncAccountVisibility();
                });
            });
        });
    </script>
@endpush
