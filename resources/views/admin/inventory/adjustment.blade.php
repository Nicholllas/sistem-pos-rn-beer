@extends('layouts.admin')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')
@section('page-subtitle', 'Sesuaikan stok sistem berdasarkan hasil pemeriksaan fisik produk.')

@section('page-actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        Kembali ke Produk
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <form action="{{ route('admin.inventory.adjustment.store') }}" method="POST" id="stockAdjustmentForm">
            @csrf

            <div class="inventory-adjustment-layout">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <span class="crud-form-avatar">
                            A
                        </span>

                        <div>
                            <h2>Informasi Penyesuaian</h2>

                            <p>
                                Masukkan hasil perhitungan stok fisik untuk
                                menyesuaikan stok yang tercatat dalam sistem.
                            </p>
                        </div>
                    </div>

                    <div class="crud-form-body">
                        <div class="inventory-adjustment-notice">
                            <span>!</span>

                            <div>
                                <strong>Lakukan setelah pengecekan fisik</strong>

                                <p>
                                    Pastikan seluruh barang telah dihitung.
                                    Sistem akan mencatat selisih sebagai pergerakan stok
                                    untuk kebutuhan audit.
                                </p>
                            </div>
                        </div>

                        <section class="crud-form-section">
                            <div class="crud-form-section-header">
                                <h3>Pilih Produk</h3>

                                <p>
                                    Pilih produk yang stoknya akan disesuaikan.
                                </p>
                            </div>

                            <label for="adjustment_product_id" class="form-label">
                                Produk
                                <span class="crud-required">*</span>
                            </label>

                            <select name="product_id" id="adjustment_product_id"
                                class="form-select crud-form-control
                                    @error('product_id') is-invalid @enderror"
                                required>
                                <option value="">
                                    Pilih produk
                                </option>

                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-name="{{ $product->name }}"
                                        data-sku="{{ $product->sku }}"
                                        data-category="{{ $product->category?->name ?? '-' }}"
                                        data-stock="{{ $product->stock }}"
                                        data-minimum-stock="{{ $product->minimum_stock }}" @selected((string) old('product_id') === (string) $product->id)>
                                        {{ $product->name }}
                                        · {{ $product->sku }}
                                        · Stok Sistem {{ $product->stock }}
                                    </option>
                                @endforeach
                            </select>

                            @error('product_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Stok sistem akan dibandingkan dengan jumlah
                                    fisik yang dimasukkan.
                                </span>
                            </div>

                            <div class="inventory-product-reference" id="adjustmentProductReference" hidden>
                                <div>
                                    <span>Stok Sistem</span>

                                    <strong id="adjustmentReferenceStock">
                                        0
                                    </strong>
                                </div>

                                <div>
                                    <span>Minimum Stok</span>

                                    <strong id="adjustmentReferenceMinimum">
                                        0
                                    </strong>
                                </div>

                                <div>
                                    <span>Kategori</span>

                                    <strong id="adjustmentReferenceCategory">
                                        -
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <section class="crud-form-section">
                            <div class="crud-form-section-header">
                                <h3>Hasil Stok Opname</h3>

                                <p>
                                    Masukkan jumlah stok yang benar-benar tersedia
                                    secara fisik.
                                </p>
                            </div>

                            <label for="actual_stock" class="form-label">
                                Stok Fisik Aktual
                                <span class="crud-required">*</span>
                            </label>

                            <input type="number" name="actual_stock" id="actual_stock"
                                class="form-control crud-form-control
                                    @error('actual_stock') is-invalid @enderror"
                                value="{{ old('actual_stock') }}" min="0" step="1"
                                placeholder="Masukkan hasil perhitungan fisik" required>

                            @error('actual_stock')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Masukkan 0 apabila produk sudah tidak tersedia
                                    secara fisik.
                                </span>
                            </div>

                            <div class="inventory-adjustment-result" id="adjustmentInlineResult" hidden>
                                <div>
                                    <span>Stok Sistem</span>

                                    <strong id="adjustmentInlineSystem">
                                        0
                                    </strong>
                                </div>

                                <div>
                                    <span>Stok Fisik</span>

                                    <strong id="adjustmentInlineActual">
                                        0
                                    </strong>
                                </div>

                                <div>
                                    <span>Selisih</span>

                                    <strong id="adjustmentInlineDifference" class="inventory-difference-value is-neutral">
                                        0
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <section class="crud-form-section">
                            <div class="crud-form-section-header">
                                <h3>Alasan Penyesuaian</h3>

                                <p>
                                    Keterangan wajib diisi agar perubahan stok
                                    dapat ditelusuri.
                                </p>
                            </div>

                            <label for="adjustment_note" class="form-label">
                                Alasan
                                <span class="crud-required">*</span>
                            </label>

                            <textarea name="note" id="adjustment_note" rows="4" maxlength="500"
                                class="form-control crud-form-control
                                    @error('note') is-invalid @enderror"
                                placeholder="Contoh: Selisih stock opname, barang rusak, barang hilang, atau koreksi salah input..." required
                                data-character-counter="adjustmentNoteCounter">{{ old('note') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Tuliskan alasan yang spesifik dan mudah dipahami.
                                </span>

                                <span id="adjustmentNoteCounter">
                                    0/500
                                </span>
                            </div>
                        </section>
                    </div>

                    <div class="crud-form-footer">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-primary" id="adjustmentSubmitButton">
                            Simpan Penyesuaian
                        </button>
                    </div>
                </section>

                <aside class="inventory-adjustment-preview">
                    <span class="inventory-preview-label">
                        Ringkasan Penyesuaian
                    </span>

                    <div class="inventory-preview-avatar" id="adjustmentProductAvatar">
                        A
                    </div>

                    <h3 id="adjustmentProductName">
                        Pilih Produk
                    </h3>

                    <p id="adjustmentProductSku">
                        Informasi produk akan ditampilkan di sini.
                    </p>

                    <div class="inventory-adjustment-comparison">
                        <div>
                            <span>Stok Sistem</span>

                            <strong id="adjustmentSystemPreview">
                                0
                            </strong>
                        </div>

                        <span class="inventory-adjustment-arrow">
                            →
                        </span>

                        <div class="is-result">
                            <span>Stok Fisik</span>

                            <strong id="adjustmentActualPreview">
                                0
                            </strong>
                        </div>
                    </div>

                    <div class="inventory-adjustment-indicator is-neutral" id="adjustmentDifferenceIndicator">
                        <span id="adjustmentDifferenceLabel">
                            Belum dihitung
                        </span>

                        <strong id="adjustmentDifferencePreview">
                            0
                        </strong>
                    </div>

                    <div class="inventory-preview-summary">
                        <div>
                            <span>Stok Sebelum</span>

                            <strong id="adjustmentBeforeSummary">
                                0
                            </strong>
                        </div>

                        <div>
                            <span>Stok Setelah</span>

                            <strong id="adjustmentAfterSummary">
                                0
                            </strong>
                        </div>

                        <div>
                            <span>Status Persediaan</span>

                            <strong id="adjustmentStockStatus" class="inventory-stock-status is-neutral">
                                Belum diketahui
                            </strong>
                        </div>
                    </div>

                    <div class="inventory-preview-note">
                        <strong>Setelah disimpan</strong>

                        <p>
                            Stok produk akan disamakan dengan stok fisik dan
                            selisihnya dicatat dalam riwayat pergerakan stok.
                        </p>
                    </div>
                </aside>
            </div>
        </form>
    </div>
@endsection
