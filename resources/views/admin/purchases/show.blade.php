@extends('layouts.admin')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail Pembelian')
@section('page-subtitle', 'Periksa informasi supplier, item, batch FIFO, dan status pembelian.')

@section('page-actions')
    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>

    @if ($purchase->status === 'completed')
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidPurchaseModal">
            Void Pembelian
        </button>
    @endif
@endsection

@section('content')
    @php
        $isVoid = $purchase->status === 'void';
        $totalQty = $purchase->items->sum('qty');
    @endphp

    <div class="crud-page purchase-detail-page">
        <section class="purchase-detail-hero {{ $isVoid ? 'is-void' : '' }}">
            <div class="purchase-detail-primary">
                <span class="purchase-detail-eyebrow">
                    Nomor Pembelian
                </span>

                <h2>{{ $purchase->purchase_number }}</h2>

                <p>
                    {{ $purchase->supplier?->name ?? 'Supplier tidak ditemukan' }}
                    ·
                    {{ $purchase->purchase_date?->format('d F Y') ?? '-' }}
                </p>
            </div>

            <div class="purchase-detail-total">
                <span>Total Pembelian</span>

                <strong>
                    Rp {{ number_format($purchase->total, 0, ',', '.') }}
                </strong>

                @if ($purchase->status === 'completed')
                    <span class="crud-status is-success">
                        Selesai
                    </span>
                @else
                    <span class="crud-status is-danger">
                        Void
                    </span>
                @endif
            </div>
        </section>

        @if ($isVoid)
            <div class="purchase-void-notice">
                <span>!</span>

                <div>
                    <strong>Pembelian telah dibatalkan</strong>

                    <p>
                        Stok dari transaksi ini telah dikurangi kembali sesuai
                        proses void pembelian.
                    </p>
                </div>
            </div>
        @endif

        <div class="purchase-detail-grid">
            <div class="purchase-detail-main">
                <section class="crud-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Informasi Pembelian</h2>

                            <p>
                                Ringkasan transaksi dan audit pencatatan.
                            </p>
                        </div>
                    </div>

                    <div class="purchase-information-grid">
                        <div>
                            <span>Supplier</span>
                            <strong>
                                {{ $purchase->supplier?->name ?? '-' }}
                            </strong>
                        </div>

                        <div>
                            <span>Tanggal Pembelian</span>
                            <strong>
                                {{ $purchase->purchase_date?->format('d M Y') ?? '-' }}
                            </strong>
                        </div>

                        <div>
                            <span>Diinput Oleh</span>
                            <strong>
                                {{ $purchase->createdBy?->name ?? '-' }}
                            </strong>
                        </div>

                        <div>
                            <span>Diinput Pada</span>
                            <strong>
                                {{ $purchase->created_at?->format('d M Y H:i') ?? '-' }}
                            </strong>
                        </div>

                        <div>
                            <span>Jumlah Jenis Produk</span>
                            <strong>
                                {{ $purchase->items->count() }} produk
                            </strong>
                        </div>

                        <div>
                            <span>Total Qty</span>
                            <strong>
                                {{ number_format($totalQty, 0, ',', '.') }} unit
                            </strong>
                        </div>
                    </div>

                    <div class="purchase-note-box">
                        <span>Catatan Pembelian</span>

                        <p>
                            {{ $purchase->note ?: 'Tidak ada catatan pembelian.' }}
                        </p>
                    </div>
                </section>

                <section class="crud-panel purchase-detail-items">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Item Pembelian</h2>

                            <p>
                                Produk dan harga modal yang membentuk batch FIFO.
                            </p>
                        </div>

                        <span class="crud-count-badge">
                            {{ $purchase->items->count() }} produk
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table crud-table purchase-detail-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Qty</th>
                                    <th>Harga Modal</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($purchase->items as $item)
                                    <tr>
                                        <td>
                                            <div class="purchase-product-cell">
                                                <span class="purchase-product-number">
                                                    {{ $loop->iteration }}
                                                </span>

                                                <div>
                                                    <strong>
                                                        {{ $item->product?->name ?? '-' }}
                                                    </strong>

                                                    <small>
                                                        SKU {{ $item->product?->sku ?? '-' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="crud-neutral-badge">
                                                {{ $item->product?->category?->name ?? '-' }}
                                            </span>
                                        </td>

                                        <td>
                                            <strong>
                                                {{ number_format($item->qty, 0, ',', '.') }}
                                            </strong>
                                        </td>

                                        <td>
                                            Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                                        </td>

                                        <td>
                                            <strong>
                                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                            </strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="4">
                                        Total Pembelian
                                    </th>

                                    <th>
                                        Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                @if ($isVoid)
                    <section class="crud-panel purchase-void-detail-panel">
                        <div class="crud-panel-header">
                            <div>
                                <h2>Informasi Void</h2>

                                <p>
                                    Audit pembatalan transaksi pembelian.
                                </p>
                            </div>
                        </div>

                        <div class="purchase-information-grid">
                            <div>
                                <span>Void Oleh</span>

                                <strong>
                                    {{ $purchase->voidedBy?->name ?? '-' }}
                                </strong>
                            </div>

                            <div>
                                <span>Void Pada</span>

                                <strong>
                                    {{ $purchase->voided_at?->format('d M Y H:i') ?? '-' }}
                                </strong>
                            </div>
                        </div>

                        <div class="purchase-note-box is-danger">
                            <span>Alasan Void</span>

                            <p>
                                {{ $purchase->void_reason ?? '-' }}
                            </p>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="purchase-detail-sidebar">
                <section class="purchase-fifo-panel">
                    <span class="purchase-fifo-label">
                        FIFO
                    </span>

                    <h3>Informasi Batch</h3>

                    <p>
                        Setiap item pembelian membuat batch FIFO baru
                        berdasarkan tanggal dan harga modal pembelian.
                    </p>

                    <div class="purchase-fifo-list">
                        <div>
                            <span>01</span>

                            <p>
                                Batch paling lama akan digunakan terlebih dahulu
                                pada transaksi penjualan.
                            </p>
                        </div>

                        <div>
                            <span>02</span>

                            <p>
                                HPP transaksi mengikuti cost price batch yang
                                dikonsumsi.
                            </p>
                        </div>

                        <div>
                            <span>03</span>

                            <p>
                                Void hanya dapat dilakukan apabila batch belum
                                pernah digunakan.
                            </p>
                        </div>
                    </div>
                </section>

                @if ($purchase->status === 'completed')
                    <section class="purchase-danger-panel">
                        <strong>Sebelum melakukan void</strong>

                        <p>
                            Sistem akan mengurangi stok kembali. Proses akan
                            ditolak jika salah satu batch sudah pernah terjual.
                        </p>

                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                            data-bs-target="#voidPurchaseModal">
                            Void Pembelian
                        </button>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    @if ($purchase->status === 'completed')
        <div class="modal fade crud-modal" id="voidPurchaseModal" tabindex="-1" aria-labelledby="voidPurchaseModalLabel"
            aria-hidden="true" data-open-on-load="{{ $errors->has('void_reason') ? 'true' : 'false' }}">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('admin.purchases.void', $purchase) }}" method="POST"
                    class="modal-content js-submit-form">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="voidPurchaseModalLabel">
                                Void Pembelian
                            </h5>

                            <p>
                                Batalkan transaksi dan kembalikan kondisi stok.
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="purchase-modal-summary">
                            <span>No. Pembelian</span>

                            <strong>
                                {{ $purchase->purchase_number }}
                            </strong>

                            <small>
                                Rp {{ number_format($purchase->total, 0, ',', '.') }}
                            </small>
                        </div>

                        <div class="purchase-modal-warning">
                            <strong>Tindakan ini memengaruhi persediaan</strong>

                            <p>
                                Stok akan dikurangi kembali dan batch FIFO dari
                                pembelian ini akan dibatalkan.
                            </p>
                        </div>

                        <div>
                            <label for="void_reason" class="form-label">
                                Alasan Void
                                <span class="crud-required">*</span>
                            </label>

                            <textarea name="void_reason" id="void_reason" rows="4"
                                class="form-control crud-form-control
                                    @error('void_reason') is-invalid @enderror"
                                placeholder="Contoh: Salah supplier, qty, atau harga modal..." required>{{ old('void_reason') }}</textarea>

                            @error('void_reason')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Jelaskan alasan pembatalan untuk kebutuhan audit.
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-danger js-submit-button"
                            data-loading-text="Memproses void...">
                            Ya, Void Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
