@extends('layouts.admin')

@section('title', 'Detail Transaksi Finance')
@section('page-title', 'Detail Transaksi Finance')
@section('page-subtitle', 'Periksa informasi transaksi, akun, referensi, status, dan riwayat audit.')

@section('page-actions')
    <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>

    @if ($financeTransaction->status === 'posted')
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
            data-bs-target="#voidFinanceTransactionModal">
            Void Transaksi
        </button>
    @endif
@endsection

@section('content')
    @include('admin.finance.partials.alerts')

    @php
        $isVoid = $financeTransaction->status === 'void';

        $typeLabel = match ($financeTransaction->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            default => 'Transfer Antar Akun',
        };

        $typeClass = match ($financeTransaction->type) {
            'income' => 'is-income',
            'expense' => 'is-expense',
            default => 'is-transfer',
        };
    @endphp

    <div class="crud-page finance-page">
        <section @class(['finance-detail-hero', 'is-void' => $isVoid])>
            <div class="finance-detail-primary">
                <span>Nomor Transaksi</span>

                <h2>{{ $financeTransaction->transaction_number }}</h2>

                <p>
                    {{ $financeTransaction->transaction_date->format('d F Y H:i') }}
                    <span aria-hidden="true">·</span>
                    {{ $financeTransaction->account?->name ?? 'Akun tidak tersedia' }}
                </p>
            </div>

            <div class="finance-detail-amount">
                <span>Nominal Transaksi</span>

                <strong @class([
                    'is-income' => $financeTransaction->type === 'income',
                    'is-expense' => $financeTransaction->type === 'expense',
                ])>
                    Rp {{ number_format($financeTransaction->amount, 0, ',', '.') }}
                </strong>

                <span @class([
                    'crud-status',
                    'is-success' => $financeTransaction->status === 'posted',
                    'is-danger' => $isVoid,
                ])>
                    {{ $financeTransaction->status === 'posted' ? 'Posted' : 'Void' }}
                </span>
            </div>
        </section>

        <div class="finance-detail-layout">
            <main class="finance-detail-main">
                <section class="crud-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Informasi Transaksi</h2>

                            <p>
                                Data utama yang membentuk pencatatan pada Cash Ledger.
                            </p>
                        </div>
                    </div>

                    <div class="finance-detail-grid">
                        <article class="finance-detail-item">
                            <span>Tanggal Transaksi</span>

                            <strong>
                                {{ $financeTransaction->transaction_date->format('d M Y H:i') }}
                            </strong>

                            <small>Waktu transaksi dicatat</small>
                        </article>

                        <article class="finance-detail-item">
                            <span>Jenis Transaksi</span>

                            <strong>
                                <span class="finance-type-badge {{ $typeClass }}">
                                    {{ $typeLabel }}
                                </span>
                            </strong>

                            <small>Klasifikasi arus dana</small>
                        </article>

                        <article class="finance-detail-item">
                            <span>
                                {{ $financeTransaction->type === 'transfer' ? 'Akun Sumber' : 'Akun' }}
                            </span>

                            <strong>
                                {{ $financeTransaction->account?->code ?? '-' }}
                                ·
                                {{ $financeTransaction->account?->name ?? '-' }}
                            </strong>

                            <small>Akun yang terpengaruh transaksi</small>
                        </article>

                        @if ($financeTransaction->type === 'transfer')
                            <article class="finance-detail-item">
                                <span>Akun Tujuan</span>

                                <strong>
                                    {{ $financeTransaction->transferAccount?->code ?? '-' }}
                                    ·
                                    {{ $financeTransaction->transferAccount?->name ?? '-' }}
                                </strong>

                                <small>Penerima transfer antar akun</small>
                            </article>
                        @endif

                        <article class="finance-detail-item">
                            <span>Kategori</span>

                            <strong>
                                {{ $financeTransaction->category?->name ?? 'Transfer Antar Akun' }}
                            </strong>

                            <small>Kategori pencatatan transaksi</small>
                        </article>

                        <article class="finance-detail-item">
                            <span>Nomor Referensi</span>

                            <strong>
                                {{ $financeTransaction->reference_number ?: '-' }}
                            </strong>

                            <small>Referensi nota atau dokumen terkait</small>
                        </article>

                        @if ($financeTransaction->reference)
                            <article class="finance-detail-item is-wide">
                                <span>Sumber Transaksi Sistem</span>

                                <strong>
                                    {{ class_basename($financeTransaction->reference_type) }}
                                    #{{ $financeTransaction->reference_id }}
                                </strong>

                                <small>Data aplikasi yang menghasilkan transaksi otomatis</small>
                            </article>
                        @endif
                    </div>

                    <div class="finance-detail-note">
                        <span>Keterangan</span>

                        <p>{{ $financeTransaction->description ?: 'Tidak ada keterangan transaksi.' }}</p>
                    </div>
                </section>

                @if ($isVoid)
                    <section class="crud-panel">
                        <div class="crud-panel-header">
                            <div>
                                <h2>Informasi Void</h2>

                                <p>
                                    Riwayat pembatalan dan alasan transaksi tidak lagi memengaruhi saldo.
                                </p>
                            </div>

                            <span class="crud-count-badge is-danger">
                                Void
                            </span>
                        </div>

                        <div class="finance-detail-grid">
                            <article class="finance-detail-item">
                                <span>Dibatalkan Oleh</span>

                                <strong>
                                    {{ $financeTransaction->voider?->name ?? '-' }}
                                </strong>

                                <small>Pengguna yang melakukan void</small>
                            </article>

                            <article class="finance-detail-item">
                                <span>Waktu Pembatalan</span>

                                <strong>
                                    {{ $financeTransaction->voided_at?->format('d M Y H:i') ?? '-' }}
                                </strong>

                                <small>Waktu void diproses oleh sistem</small>
                            </article>
                        </div>

                        <div class="finance-detail-note">
                            <span>Alasan Void</span>

                            <p>{{ $financeTransaction->void_reason ?: '-' }}</p>
                        </div>
                    </section>
                @endif
            </main>

            <aside class="finance-detail-sidebar">
                <section class="crud-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Informasi Audit</h2>

                            <p>
                                Jejak pembuatan dan perubahan data.
                            </p>
                        </div>
                    </div>

                    <div class="finance-detail-grid is-single-column">
                        <article class="finance-detail-item">
                            <span>Dibuat Oleh</span>

                            <strong>
                                {{ $financeTransaction->creator?->name ?? '-' }}
                            </strong>

                            <small>Pengguna pencatat transaksi</small>
                        </article>

                        <article class="finance-detail-item">
                            <span>Dibuat Pada</span>

                            <strong>
                                {{ $financeTransaction->created_at?->format('d M Y H:i') ?? '-' }}
                            </strong>

                            <small>Waktu data tersimpan</small>
                        </article>

                        <article class="finance-detail-item">
                            <span>Terakhir Diperbarui</span>

                            <strong>
                                {{ $financeTransaction->updated_at?->format('d M Y H:i') ?? '-' }}
                            </strong>

                            <small>Waktu perubahan terakhir</small>
                        </article>
                    </div>
                </section>

                @if ($financeTransaction->status === 'posted')
                    <section class="finance-danger-panel">
                        <strong>Sebelum melakukan void</strong>

                        <p>
                            Transaksi akan tetap tersimpan sebagai histori audit dan tidak lagi memengaruhi saldo.
                        </p>

                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                            data-bs-target="#voidFinanceTransactionModal">
                            Void Transaksi
                        </button>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    @if ($financeTransaction->status === 'posted')
        <div class="modal fade crud-modal" id="voidFinanceTransactionModal" tabindex="-1"
            aria-labelledby="voidFinanceTransactionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('admin.finance.transactions.void', $financeTransaction) }}" method="POST"
                    class="modal-content js-submit-form">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="voidFinanceTransactionModalLabel">
                                Void Transaksi Finance
                            </h5>

                            <p>
                                Batalkan pengaruh transaksi pada saldo dengan tetap menyimpan histori.
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="finance-modal-summary">
                            <span>Nomor Transaksi</span>

                            <strong>
                                {{ $financeTransaction->transaction_number }}
                            </strong>

                            <small>
                                Rp {{ number_format($financeTransaction->amount, 0, ',', '.') }}
                            </small>
                        </div>

                        <div class="crud-notice">
                            <div>
                                <strong>Transaksi akan dibatalkan</strong>

                                <p>
                                    Saldo akun akan disesuaikan kembali dan tindakan ini tercatat dalam audit.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="void_reason" class="form-label">
                                Alasan Pembatalan
                                <span class="crud-required">*</span>
                            </label>

                            <textarea name="void_reason" id="void_reason" rows="4"
                                class="form-control crud-form-control @error('void_reason') is-invalid @enderror" minlength="5" maxlength="1000"
                                placeholder="Jelaskan alasan transaksi dibatalkan..." required>{{ old('void_reason') }}</textarea>

                            @error('void_reason')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>Alasan disimpan untuk kebutuhan audit.</span>
                                <span>5–1.000 karakter</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-danger js-submit-button"
                            data-loading-text="Memproses void...">
                            Ya, Void Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
