@extends('layouts.admin')

@section('title', 'Detail Transaksi Finance')
@section('page-title', 'Detail Transaksi Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        {{ $financeTransaction->transaction_number }}
                    </h3>

                    @if ($financeTransaction->status === 'posted')
                        <span class="badge bg-success">
                            Posted
                        </span>
                    @else
                        <span class="badge bg-secondary">
                            Void
                        </span>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th width="230">Nomor Transaksi</th>
                                    <td>
                                        {{ $financeTransaction->transaction_number }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Tanggal Transaksi</th>
                                    <td>
                                        {{ $financeTransaction->transaction_date->format('d/m/Y H:i') }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Jenis Transaksi</th>
                                    <td>
                                        @if ($financeTransaction->type === 'income')
                                            <span class="badge bg-success">
                                                Pemasukan
                                            </span>
                                        @elseif ($financeTransaction->type === 'expense')
                                            <span class="badge bg-danger">
                                                Pengeluaran
                                            </span>
                                        @else
                                            <span class="badge bg-primary">
                                                Transfer Antar Akun
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        {{ $financeTransaction->type === 'transfer' ? 'Akun Sumber' : 'Akun' }}
                                    </th>
                                    <td>
                                        {{ $financeTransaction->account?->code ?? '-' }}
                                        -
                                        {{ $financeTransaction->account?->name ?? '-' }}
                                    </td>
                                </tr>

                                @if ($financeTransaction->type === 'transfer')
                                    <tr>
                                        <th>Akun Tujuan</th>
                                        <td>
                                            {{ $financeTransaction->transferAccount?->code ?? '-' }}
                                            -
                                            {{ $financeTransaction->transferAccount?->name ?? '-' }}
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <th>Kategori</th>
                                    <td>
                                        {{ $financeTransaction->category?->name ?? 'Transfer Antar Akun' }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Nominal</th>
                                    <td class="fs-5 fw-bold">
                                        Rp{{ number_format($financeTransaction->amount, 0, ',', '.') }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Nomor Referensi</th>
                                    <td>
                                        {{ $financeTransaction->reference_number ?: '-' }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Keterangan</th>
                                    <td>
                                        {!! nl2br(e($financeTransaction->description ?: '-')) !!}
                                    </td>
                                </tr>

                                @if ($financeTransaction->reference)
                                    <tr>
                                        <th>Sumber Transaksi Sistem</th>
                                        <td>
                                            {{ class_basename($financeTransaction->reference_type) }}
                                            #{{ $financeTransaction->reference_id }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Informasi Audit
                    </h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted">
                            Dibuat Oleh
                        </div>

                        <div>
                            {{ $financeTransaction->creator?->name ?? '-' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">
                            Dibuat Pada
                        </div>

                        <div>
                            {{ $financeTransaction->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="small text-muted">
                            Terakhir Diperbarui
                        </div>

                        <div>
                            {{ $financeTransaction->updated_at?->format('d/m/Y H:i') ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            @if ($financeTransaction->status === 'posted')
                <div class="card border-danger">
                    <div class="card-header">
                        <h3 class="card-title mb-0 text-danger">
                            Batalkan Transaksi
                        </h3>
                    </div>

                    <form action="{{ route('admin.finance.transactions.void', $financeTransaction) }}" method="POST"
                        onsubmit="return confirm('Transaksi ini akan dibatalkan dan tidak lagi memengaruhi saldo. Lanjutkan?')">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="alert alert-warning">
                                Transaksi yang dibatalkan tetap tersimpan sebagai histori audit.
                            </div>

                            <label for="void_reason" class="form-label">
                                Alasan Pembatalan
                                <span class="text-danger">*</span>
                            </label>

                            <textarea name="void_reason" id="void_reason" class="form-control @error('void_reason') is-invalid @enderror"
                                rows="4" minlength="5" maxlength="1000" required>{{ old('void_reason') }}</textarea>

                            @error('void_reason')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-danger w-100">
                                Void Transaksi
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card border-secondary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            Informasi Void
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <div class="small text-muted">
                                Dibatalkan Oleh
                            </div>

                            <div>
                                {{ $financeTransaction->voider?->name ?? '-' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="small text-muted">
                                Waktu Pembatalan
                            </div>

                            <div>
                                {{ $financeTransaction->voided_at?->format('d/m/Y H:i') ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="small text-muted">
                                Alasan
                            </div>

                            <div>
                                {!! nl2br(e($financeTransaction->void_reason ?: '-')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
