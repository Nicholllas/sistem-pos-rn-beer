@extends('layouts.admin')

@section('title', 'Laporan Penutupan Shift & Rekonsiliasi Kas #' . $shift->id)
@section('page-title', 'Laporan Penutupan Shift & Rekonsiliasi Kas')
@section('page-subtitle', 'Ringkasan penerimaan kas, hasil perhitungan uang fisik, dan status penyelesaian selisih.')

@section('page-actions')
    <a href="{{ route('admin.sales.pos') }}" class="btn btn-primary">
        Kembali ke POS
    </a>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary">
            Daftar Shift
        </a>
    @endif
@endsection

@section('content')
    @php
        $diff = (float) ($shift->cash_discrepancy ?? 0);
        $isShortage = $diff < 0;
    @endphp

    <div class="row receipt-layout">
        <div class="col-md-7 receipt-main-column">
            <div class="d-flex justify-content-center">
                <div class="thermal-receipt" id="receiptArea">
                    <div class="receipt-header">
                        <h4>RN BEER</h4>
                        <p>LAPORAN PENUTUPAN SHIFT</p>
                        <p>Shift #{{ $shift->id }}</p>
                    </div>

                    <div class="receipt-line"></div>

                    <div class="receipt-info">
                        <div>
                            <span>Kasir</span>
                            <span>{{ $shift->user?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span>Buka</span>
                            <span>{{ $shift->opened_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </div>
                        <div>
                            <span>Tutup</span>
                            <span>{{ $shift->closed_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </div>
                        @if ($shift->closedBy)
                            <div>
                                <span>Ditutup Oleh</span>
                                <span>{{ $shift->closedBy->name }}</span>
                            </div>
                        @endif
                        @if ($shift->closedSupervisor)
                            <div>
                                <span>Otorisasi</span>
                                <span>{{ $shift->closedSupervisor->name }}</span>
                            </div>
                        @endif
                        <div>
                            <span>Status</span>
                            <span>DITUTUP</span>
                        </div>
                    </div>

                    <div class="receipt-line"></div>

                    <div class="receipt-summary">
                        <div>
                            <span>Tunai Bersih</span>
                            <span>Rp {{ number_format((float) $shift->total_cash_sales, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span>QRIS</span>
                            <span>Rp {{ number_format((float) $shift->total_qris_sales, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span>Transfer</span>
                            <span>Rp {{ number_format((float) $shift->total_transfer_sales, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <strong>Total Penjualan</strong>
                            <strong>Rp {{ number_format((float) $shift->total_sales, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="receipt-line"></div>

                    <div class="receipt-summary">
                        <div>
                            <span>Modal Awal</span>
                            <span>Rp {{ number_format((float) $shift->starting_cash, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span>(+) Tunai Masuk</span>
                            <span>Rp {{ number_format((float) $shift->total_cash_sales, 0, ',', '.') }}</span>
                        </div>
                        @if ((float) $shift->total_cash_refund > 0)
                            <div>
                                <span>Info Void Tunai</span>
                                <span>Rp {{ number_format((float) $shift->total_cash_refund, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div>
                            <strong>Ekspektasi Kas</strong>
                            <strong>Rp {{ number_format((float) $shift->expected_cash, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <strong>Kas Fisik</strong>
                            <strong>Rp {{ number_format((float) ($shift->actual_cash ?? 0), 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <strong>Selisih Kas</strong>
                            <strong>
                                @if ($diff == 0)
                                    SESUAI (Rp 0)
                                @elseif ($diff < 0)
                                    KURANG (-Rp {{ number_format(abs($diff), 0, ',', '.') }})
                                @else
                                    LEBIH (+Rp {{ number_format($diff, 0, ',', '.') }})
                                @endif
                            </strong>
                        </div>
                        @if ($shift->hasDiscrepancy())
                            <div>
                                <span>Penyelesaian</span>
                                <span>
                                    {{ $shift->discrepancy_status === 'resolved' ? strtoupper($shift->discrepancyResolutionLabel()) : 'PENDING' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    @if ($shift->closing_notes)
                        <div class="receipt-line"></div>
                        <div class="receipt-footer" style="text-align: left;">
                            <p>Catatan: {{ $shift->closing_notes }}</p>
                        </div>
                    @endif

                    @if ($shift->discrepancy_status === 'resolved' && $shift->resolution_notes)
                        <div class="receipt-line"></div>
                        <div class="receipt-footer" style="text-align: left;">
                            <p>Penyelesaian: {{ $shift->resolution_notes }}</p>
                        </div>
                    @endif

                    <div class="receipt-line"></div>

                    <div class="receipt-footer">
                        <p>Tanda Tangan Kasir / Supervisor</p>
                        <p style="margin-top: 28px;">( {{ $shift->user?->name ?? 'Kasir' }} )</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5 d-print-none">
            <div class="card receipt-action-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="receipt-action-heading">
                        <h3 class="card-title">Ringkasan Penutupan Shift</h3>
                        <small>Shift #{{ $shift->id }} · {{ $shift->user?->name ?? '-' }}</small>
                    </div>

                    @if ($diff == 0)
                        <span class="crud-status is-success">Sesuai</span>
                    @elseif ($diff < 0)
                        <span class="crud-status is-danger">Kurang</span>
                    @else
                        <span class="crud-status is-warning">Lebih</span>
                    @endif
                </div>

                <div class="card-body">
                    <div class="payment-overview">
                        <div class="payment-overview-row">
                            <span>Ekspektasi Kas di Laci</span>
                            <strong>Rp {{ number_format((float) $shift->expected_cash, 0, ',', '.') }}</strong>
                        </div>
                        <div class="payment-overview-row">
                            <span>Kas Fisik Dihitung</span>
                            <strong>Rp {{ number_format((float) ($shift->actual_cash ?? 0), 0, ',', '.') }}</strong>
                        </div>
                        <div class="payment-overview-row payment-change-row">
                            <span>Selisih Kas</span>
                            <strong>
                                @if ($diff == 0)
                                    Rp 0
                                @elseif ($diff < 0)
                                    -Rp {{ number_format(abs($diff), 0, ',', '.') }}
                                @else
                                    +Rp {{ number_format($diff, 0, ',', '.') }}
                                @endif
                            </strong>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary w-100" onclick="window.print()">
                            Cetak Laporan Penutupan
                        </button>
                        <a href="{{ route('admin.sales.pos') }}" class="btn btn-success w-100">
                            Buka Shift Baru
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary w-100">
                                Daftar Shift &amp; Rekonsiliasi
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if (auth()->user()->isAdmin() && $shift->hasDiscrepancy())
                <section class="crud-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Penyelesaian Selisih Kas</h2>
                            <p>Sinkronkan selisih kas ke Cash Ledger atau selesaikan secara administratif.</p>
                        </div>
                        @if ($shift->isPendingDiscrepancy())
                            <span class="crud-count-badge is-warning">Pending</span>
                        @else
                            <span class="crud-count-badge is-success">Selesai</span>
                        @endif
                    </div>

                    <div class="crud-panel-body">
                        @if ($shift->isPendingDiscrepancy())
                            @if (! $isShortage)
                                <div class="purchase-process-info mt-0 mb-3">
                                    <strong>Barang terjual lupa diinput kasir di POS?</strong>
                                    <p class="small mb-2 mt-1">Catat penjualan susulan untuk memotong stok FIFO dan menambah pemasukan penjualan tanpa mengotori kas shift hari ini.</p>
                                    <a href="{{ route('admin.shifts.catchup-sale.create', $shift) }}" class="btn btn-sm btn-primary w-100">
                                        Catat Penjualan Susulan (+Rp {{ number_format($diff, 0, ',', '.') }})
                                    </a>
                                </div>
                            @endif

                            <form action="{{ route('admin.shifts.resolve-discrepancy', $shift) }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="resolution" class="form-label">Metode Penyelesaian <span class="crud-required">*</span></label>
                                    <select name="resolution" id="resolution" class="form-select crud-form-control @error('resolution') is-invalid @enderror" data-no-combobox required>
                                        <option value="ledger_adjustment" @selected(old('resolution', 'ledger_adjustment') === 'ledger_adjustment')>
                                            Sesuaikan ke Cash Ledger ({{ $isShortage ? 'Catat Pengeluaran Selisih Kurang' : 'Catat Pemasukan Selisih Lebih' }})
                                        </option>
                                        @if ($isShortage)
                                            <option value="cashier_covered" @selected(old('resolution') === 'cashier_covered')>
                                                Diganti / Disetor oleh Kasir (Saldo Cash Ledger Tetap)
                                            </option>
                                        @endif
                                        <option value="manual_resolved" @selected(old('resolution') === 'manual_resolved')>
                                            Diselesaikan Manual Tanpa Penyesuaian Ledger
                                        </option>
                                    </select>
                                    @error('resolution')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3" id="summaryAccountWrapper">
                                    <label for="finance_account_id" class="form-label">Akun Cash Ledger <span class="crud-required">*</span></label>
                                    <select name="finance_account_id" id="finance_account_id" class="form-select crud-form-control @error('finance_account_id') is-invalid @enderror" data-no-combobox>
                                        @foreach ($financeAccounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('finance_account_id', $financeAccounts->first()?->id) == $account->id)>
                                                {{ $account->name }} ({{ strtoupper($account->type) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('finance_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="resolution_notes" class="form-label">Catatan Penyelesaian <span class="crud-required">*</span></label>
                                    <textarea name="resolution_notes" id="resolution_notes" rows="3"
                                        class="form-control crud-form-control @error('resolution_notes') is-invalid @enderror"
                                        placeholder="Contoh: Disetujui oleh owner sebagai selisih uang kembalian..." required>{{ old('resolution_notes') }}</textarea>
                                    @error('resolution_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    Simpan Penyelesaian Selisih
                                </button>
                            </form>
                        @else
                            <div class="crud-kv-list">
                                <div class="crud-kv-row">
                                    <span>Diselesaikan Oleh</span>
                                    <strong>{{ $shift->resolvedBy?->name ?? '-' }}</strong>
                                </div>
                                <div class="crud-kv-row">
                                    <span>Waktu Selesai</span>
                                    <strong>{{ $shift->resolved_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                                </div>
                                <div class="crud-kv-row">
                                    <span>Metode</span>
                                    <strong>{{ $shift->discrepancyResolutionLabel() }}</strong>
                                </div>
                                @if ($shift->catchupSale)
                                    <div class="crud-kv-row">
                                        <span>Invoice Susulan</span>
                                        <strong>
                                            <a href="{{ route('admin.sales.receipt', $shift->catchupSale) }}">
                                                {{ $shift->catchupSale->invoice_number }}
                                            </a>
                                        </strong>
                                    </div>
                                @endif
                                @if ($shift->financeTransaction)
                                    <div class="crud-kv-row">
                                        <span>Cash Ledger</span>
                                        <strong>
                                            <a href="{{ route('admin.finance.transactions.show', $shift->financeTransaction) }}">
                                                {{ $shift->financeTransaction->transaction_number }}
                                            </a>
                                        </strong>
                                    </div>
                                @endif
                            </div>

                            @if ($shift->resolution_notes)
                                <div class="crud-info-box">
                                    <strong>Catatan Penyelesaian</strong>
                                    <p>{{ $shift->resolution_notes }}</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resolutionSelect = document.getElementById('resolution');
            const accountWrapper = document.getElementById('summaryAccountWrapper');

            if (resolutionSelect && accountWrapper) {
                const toggleAccountField = () => {
                    const needsAccount = resolutionSelect.value === 'ledger_adjustment';
                    accountWrapper.classList.toggle('d-none', !needsAccount);
                };

                resolutionSelect.addEventListener('change', toggleAccountField);
                toggleAccountField();
            }
        });
    </script>
@endpush
