@extends('layouts.admin')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail Pembelian')
@section('page-subtitle', 'Periksa informasi supplier, item, batch FIFO, akun pembayaran, dan status pembelian.')

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
        $statusLabel =
            $purchase->status === 'completed'
                ? 'Selesai'
                : ($purchase->status === 'void'
                    ? 'Void'
                    : ucfirst($purchase->status ?? '-'));
    @endphp

    <div class="crud-page purchase-detail-page">
        <section @class(['purchase-detail-hero', 'is-void' => $isVoid])>
            <div class="purchase-detail-primary">
                <span class="purchase-detail-eyebrow">
                    Nomor Pembelian
                </span>

                <h2>{{ $purchase->purchase_number }}</h2>

                <p>
                    {{ $purchase->supplier?->name ?? 'Supplier tidak ditemukan' }}
                    <span aria-hidden="true">·</span>
                    {{ $purchase->purchase_date?->format('d F Y') ?? '-' }}
                </p>
            </div>

            <div class="purchase-detail-total">
                <span>Total Pembelian</span>

                <strong>
                    Rp {{ number_format($purchase->total, 0, ',', '.') }}
                </strong>

                <span @class([
                    'crud-status',
                    'is-success' => $purchase->status === 'completed',
                    'is-danger' => $purchase->status === 'void',
                    'is-muted' => !in_array($purchase->status, ['completed', 'void'], true),
                ])>
                    {{ $statusLabel }}
                </span>
            </div>
        </section>

        @if ($isVoid)
            <section class="purchase-void-notice">
                <strong>Pembelian telah dibatalkan</strong>

                <p>
                    Stok dari transaksi ini telah dikurangi kembali sesuai proses
                    void pembelian.
                </p>
            </section>
        @endif

        <div class="purchase-detail-layout">
            <main class="purchase-detail-main">
                <section class="crud-panel purchase-detail-information-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Informasi Pembelian</h2>

                            <p>
                                Ringkasan transaksi, akun pembayaran, dan audit pencatatan.
                            </p>
                        </div>
                    </div>

                    <div class="purchase-detail-info-grid">
                        <article class="purchase-detail-info-card">
                            <span>Supplier</span>

                            <strong>
                                {{ $purchase->supplier?->name ?? '-' }}
                            </strong>

                            <small>
                                Supplier transaksi pembelian
                            </small>
                        </article>

                        <article class="purchase-detail-info-card purchase-detail-info-card--wide">
                            <span>Akun Pembayaran</span>

                            @if ($purchase->financeAccount)
                                <strong>
                                    {{ $purchase->financeAccount->code }}
                                    <span aria-hidden="true">·</span>
                                    {{ $purchase->financeAccount->name }}
                                </strong>

                                <small>
                                    Akun yang digunakan untuk membayar pembelian
                                </small>
                            @else
                                <strong class="is-muted">
                                    Belum terhubung ke akun keuangan
                                </strong>

                                <small>
                                    Pembelian lama sebelum modul keuangan diterapkan
                                </small>
                            @endif
                        </article>

                        <article class="purchase-detail-info-card">
                            <span>Tanggal Pembelian</span>

                            <strong>
                                {{ $purchase->purchase_date?->format('d M Y') ?? '-' }}
                            </strong>

                            <small>
                                Tanggal pembentukan batch FIFO
                            </small>
                        </article>

                        <article class="purchase-detail-info-card">
                            <span>Diinput Oleh</span>

                            <strong>
                                {{ $purchase->createdBy?->name ?? '-' }}
                            </strong>

                            <small>
                                Pengguna pencatat transaksi
                            </small>
                        </article>

                        <article class="purchase-detail-info-card">
                            <span>Diinput Pada</span>

                            <strong>
                                {{ $purchase->created_at?->format('d M Y H:i') ?? '-' }}
                            </strong>

                            <small>
                                Waktu pencatatan sistem
                            </small>
                        </article>

                        <article class="purchase-detail-info-card">
                            <span>Jumlah Jenis Produk</span>

                            <strong>
                                {{ number_format($purchase->items->count(), 0, ',', '.') }}
                                produk
                            </strong>

                            <small>
                                Produk berbeda dalam pembelian
                            </small>
                        </article>

                        <article class="purchase-detail-info-card">
                            <span>Total Qty</span>

                            <strong>
                                {{ number_format($totalQty, 0, ',', '.') }}
                                unit
                            </strong>

                            <small>
                                Total unit stok yang dibeli
                            </small>
                        </article>

                        <article class="purchase-detail-info-card">
                            <span>Status Transaksi</span>

                            <strong>
                                {{ $statusLabel }}
                            </strong>

                            <small>
                                Status pembelian saat ini
                            </small>
                        </article>
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
                            {{ number_format($purchase->items->count(), 0, ',', '.') }}
                            produk
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
                                @forelse ($purchase->items as $item)
                                    <tr>
                                        <td>
                                            <div class="purchase-product-cell">
                                                <strong>
                                                    {{ $item->product?->name ?? 'Produk tidak ditemukan' }}
                                                </strong>

                                                <span>
                                                    SKU {{ $item->product?->sku ?? '-' }}
                                                </span>

                                                <small>
                                                    Item pembelian
                                                </small>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="crud-neutral-badge">
                                                {{ $item->product?->category?->name ?? 'Tanpa kategori' }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="purchase-item-quantity">
                                                <strong>
                                                    {{ number_format($item->qty, 0, ',', '.') }}
                                                </strong>

                                                <small>unit</small>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="purchase-item-money">
                                                <strong>
                                                    Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                                                </strong>

                                                <small>modal per unit</small>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="purchase-item-money">
                                                <strong>
                                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                                </strong>

                                                <small>subtotal item</small>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="crud-empty-state">
                                                <strong>Tidak ada item pembelian</strong>

                                                <p>
                                                    Item pada transaksi ini tidak tersedia.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
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

                        <div class="purchase-detail-info-grid purchase-detail-info-grid--two">
                            <article class="purchase-detail-info-card">
                                <span>Void Oleh</span>

                                <strong>
                                    {{ $purchase->voidedBy?->name ?? '-' }}
                                </strong>

                                <small>
                                    Pengguna yang membatalkan transaksi
                                </small>
                            </article>

                            <article class="purchase-detail-info-card">
                                <span>Void Pada</span>

                                <strong>
                                    {{ $purchase->voided_at?->format('d M Y H:i') ?? '-' }}
                                </strong>

                                <small>
                                    Waktu pembatalan transaksi
                                </small>
                            </article>
                        </div>

                        <div class="purchase-note-box is-danger">
                            <span>Alasan Void</span>

                            <p>
                                {{ $purchase->void_reason ?: '-' }}
                            </p>
                        </div>
                    </section>
                @endif
            </main>

            <aside class="purchase-detail-sidebar">
                <section class="purchase-fifo-panel">
                    <span class="purchase-fifo-label">
                        FIFO
                    </span>

                    <h3>Informasi Batch</h3>

                    <p>
                        Setiap item pembelian membuat batch FIFO baru berdasarkan
                        tanggal dan harga modal pembelian.
                    </p>

                    <div class="purchase-fifo-list">
                        <div>
                            <span>01</span>

                            <p>
                                Batch paling lama digunakan lebih dahulu pada
                                transaksi penjualan.
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
                            Sistem akan mengurangi stok kembali. Proses ditolak
                            jika salah satu batch sudah pernah terjual.
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
                            <strong>
                                Tindakan ini memengaruhi persediaan
                            </strong>

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
