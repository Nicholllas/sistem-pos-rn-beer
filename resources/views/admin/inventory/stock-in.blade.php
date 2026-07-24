@extends('layouts.admin')

@section('title', 'Stok Masuk Manual')
@section('page-title', 'Stok Masuk Manual')
@section('page-subtitle', 'Tambahkan persediaan secara manual dan buat batch FIFO baru.')

@section('page-actions')
    <a href="{{ route('admin.inventory.logs') }}" class="btn btn-outline-secondary">
        Lihat Log Stok
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <form action="{{ route('admin.inventory.stock-in.store') }}" method="POST" id="stockInForm">
            @csrf

            <div class="inventory-stock-layout">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <span class="crud-form-avatar">
                            S
                        </span>

                        <div>
                            <h2>Informasi Stok Masuk</h2>

                            <p>
                                Pilih produk, masukkan jumlah stok, dan tentukan
                                harga modal untuk batch FIFO baru.
                            </p>
                        </div>
                    </div>

                    <div class="crud-form-body">
                        <div class="crud-notice crud-notice-info">
                            <span class="crud-notice-avatar">
                                i
                            </span>

                            <div>
                                <strong>Stok masuk manual membuat batch FIFO</strong>

                                <p>
                                    Gunakan menu Pembelian Stok apabila barang
                                    berasal dari transaksi pembelian supplier yang
                                    perlu dicatat secara lengkap.
                                </p>
                            </div>
                        </div>

                        <section class="crud-form-section">
                            <div class="crud-form-section-header">
                                <h3>Produk</h3>

                                <p>
                                    Pilih produk yang akan ditambahkan stoknya.
                                </p>
                            </div>

                            <label for="product_id" class="form-label">
                                Produk
                                <span class="crud-required">*</span>
                            </label>

                            <select name="product_id" id="product_id"
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
                                        data-stock="{{ $product->stock }}" data-cost-price="{{ $product->cost_price }}"
                                        @selected((string) old('product_id') === (string) $product->id)>
                                        {{ $product->name }}
                                        · {{ $product->sku }}
                                        · Stok {{ $product->stock }}
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
                                    Daftar hanya menampilkan produk yang tersedia
                                    untuk pengelolaan stok.
                                </span>
                            </div>

                            <div class="inventory-product-reference" id="stockProductReference" hidden>
                                <div>
                                    <span>Stok Saat Ini</span>
                                    <strong id="stockReferenceCurrent">0</strong>
                                </div>

                                <div>
                                    <span>Harga Modal Produk</span>
                                    <strong id="stockReferenceCost">Rp 0</strong>
                                </div>

                                <div>
                                    <span>Kategori</span>
                                    <strong id="stockReferenceCategory">-</strong>
                                </div>
                            </div>
                        </section>

                        <section class="crud-form-section">
                            <div class="crud-form-section-header">
                                <h3>Jumlah dan Harga Modal</h3>

                                <p>
                                    Nilai ini menentukan jumlah stok dan cost
                                    price batch FIFO yang dibuat.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="qty" class="form-label">
                                        Jumlah Masuk
                                        <span class="crud-required">*</span>
                                    </label>

                                    <input type="number" name="qty" id="qty"
                                        class="form-control crud-form-control
                                            @error('qty') is-invalid @enderror"
                                        value="{{ old('qty') }}" min="1" step="1" placeholder="Contoh: 24"
                                        required>

                                    @error('qty')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                    <div class="crud-field-help">
                                        <span>
                                            Barang 1 dus isi 24 berarti input 24.
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="cost_price" class="form-label">
                                        Harga Modal per Produk
                                        <span class="crud-required">*</span>
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">
                                            Rp
                                        </span>

                                        <input type="number" name="cost_price" id="cost_price"
                                            class="form-control crud-form-control
                                                @error('cost_price') is-invalid @enderror"
                                            value="{{ old('cost_price') }}" min="0" step="1"
                                            placeholder="15000" required>
                                    </div>

                                    @error('cost_price')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                    <div class="crud-field-help">
                                        <span>
                                            Harga aktual per unit untuk batch ini.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="crud-form-section">
                            <div class="crud-form-section-header">
                                <h3>Catatan</h3>

                                <p>
                                    Tambahkan keterangan untuk kebutuhan audit stok.
                                </p>
                            </div>

                            <label for="note" class="form-label">
                                Catatan Stok Masuk
                            </label>

                            <textarea name="note" id="note" rows="4" maxlength="500"
                                class="form-control crud-form-control
                                    @error('note') is-invalid @enderror"
                                placeholder="Contoh: Barang bonus supplier atau koreksi penerimaan stok..."
                                data-character-counter="stockInNoteCounter">{{ old('note') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Jelaskan sumber atau alasan stok masuk manual.
                                </span>

                                <span id="stockInNoteCounter">
                                    0/500
                                </span>
                            </div>
                        </section>
                    </div>

                    <div class="crud-form-footer">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-primary" id="stockInSubmitButton">
                            Simpan Stok Masuk
                        </button>
                    </div>
                </section>

                <aside class="inventory-stock-preview">
                    <span class="inventory-preview-label">
                        Ringkasan Stok
                    </span>

                    <div class="inventory-preview-avatar" id="stockProductAvatar">
                        S
                    </div>

                    <h3 id="stockProductName">
                        Pilih Produk
                    </h3>

                    <p id="stockProductSku">
                        Informasi produk akan ditampilkan di sini.
                    </p>

                    <div class="inventory-stock-flow">
                        <div>
                            <span>Stok Awal</span>
                            <strong id="stockBeforePreview">0</strong>
                        </div>

                        <span class="inventory-stock-arrow">
                            +
                        </span>

                        <div>
                            <span>Stok Masuk</span>
                            <strong id="stockAddedPreview">0</strong>
                        </div>

                        <span class="inventory-stock-arrow">
                            =
                        </span>

                        <div class="is-result">
                            <span>Stok Akhir</span>
                            <strong id="stockAfterPreview">0</strong>
                        </div>
                    </div>

                    <div class="inventory-preview-summary">
                        <div>
                            <span>Harga Modal per Unit</span>
                            <strong id="stockCostPreview">
                                Rp 0
                            </strong>
                        </div>

                        <div>
                            <span>Nilai Stok Masuk</span>
                            <strong id="stockValuePreview">
                                Rp 0
                            </strong>
                        </div>

                        <div>
                            <span>Batch FIFO Baru</span>
                            <strong>
                                1 batch
                            </strong>
                        </div>
                    </div>

                    <div class="inventory-preview-note">
                        <strong>Setelah disimpan</strong>

                        <p>
                            Stok produk bertambah, batch FIFO dibuat, dan
                            pergerakannya tercatat pada log stok.
                        </p>
                    </div>
                </aside>
            </div>
        </form>
    </div>
@endsection
