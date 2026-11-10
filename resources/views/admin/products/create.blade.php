@extends('layouts.admin')

@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Produk')
@section('page-subtitle', 'Daftarkan identitas, harga, dan pengaturan awal produk.')

@section('page-actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <section class="crud-form-panel">
            <div class="crud-form-header">
                <div>
                    <h2>Informasi Produk Baru</h2>

                    <p>
                        Nama produk dan SKU dibuat otomatis dari identitas
                        produk yang dimasukkan.
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data"
                class="js-submit-form">
                @csrf

                <div class="crud-form-body">
                    <div class="crud-notice crud-notice-info">
                        <span class="crud-notice-avatar">
                            i
                        </span>

                        <div>
                            <strong>Stok awal otomatis bernilai 0</strong>

                            <p>
                                Penambahan stok dilakukan melalui Pembelian Stok
                                atau Stok Masuk Manual.
                            </p>
                        </div>
                    </div>

                    <div class="product-form-layout">
                        <div class="product-form-main">
                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Identitas Produk</h3>

                                    <p>
                                        Informasi utama yang membentuk nama produk
                                        dan SKU.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">
                                            Kategori
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="category_id" id="category_id"
                                            class="form-select crud-form-control
                                                @error('category_id') is-invalid @enderror"
                                            required>
                                            <option value="">
                                                Pilih kategori
                                            </option>

                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('category_id')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="brand" class="form-label">
                                            Brand
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="text" name="brand" id="brand"
                                            class="form-control crud-form-control
                                                @error('brand') is-invalid @enderror"
                                            value="{{ old('brand') }}" placeholder="Contoh: BINTANG" required
                                            maxlength="100" autocomplete="off" autofocus
                                            data-character-counter="brandCounter">

                                        @error('brand')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>Disimpan dalam huruf kapital.</span>
                                            <span id="brandCounter">0/100</span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="variant" class="form-label">
                                            Varian / Jenis
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="text" name="variant" id="variant"
                                            class="form-control crud-form-control
                                                @error('variant') is-invalid @enderror"
                                            value="{{ old('variant') }}" placeholder="Contoh: ANGGUR MERAH" required
                                            maxlength="150" autocomplete="off" data-character-counter="variantCounter">

                                        @error('variant')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>Contoh: ANGGUR MERAH, INTISARI, PILSENER, MILD.</span>
                                            <span id="variantCounter">0/150</span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="packaging_type" class="form-label">
                                            Jenis Kemasan
                                        </label>

                                        <select name="packaging_type" id="packaging_type"
                                            class="form-select crud-form-control
                                                @error('packaging_type') is-invalid @enderror">
                                            <option value="">-- Tanpa Kemasan / Opsional --</option>
                                            <option value="BOTOL BESAR" @selected(old('packaging_type') === 'BOTOL BESAR')>Botol Besar</option>
                                            <option value="BOTOL KECIL" @selected(old('packaging_type') === 'BOTOL KECIL')>Botol Kecil</option>
                                            <option value="KALENG BESAR" @selected(old('packaging_type') === 'KALENG BESAR')>Kaleng Besar</option>
                                            <option value="KALENG KECIL" @selected(old('packaging_type') === 'KALENG KECIL')>Kaleng Kecil</option>
                                        </select>

                                        @error('packaging_type')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>Opsional. Kosongkan jika bukan produk kemasan minuman.</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="size" class="form-label">
                                            Ukuran
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="number" name="size" id="size"
                                            class="form-control crud-form-control
                                                @error('size') is-invalid @enderror"
                                            value="{{ old('size') }}" placeholder="620" min="1" required>

                                        @error('size')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label for="unit" class="form-label">
                                            Satuan
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="unit" id="unit"
                                            class="form-select crud-form-control
                                                @error('unit') is-invalid @enderror"
                                            required>
                                            <option value="">Pilih</option>
                                            <option value="ML" @selected(old('unit') === 'ML')>ML</option>
                                            <option value="L" @selected(old('unit') === 'L')>L</option>
                                            <option value="GR" @selected(old('unit') === 'GR')>GR</option>
                                            <option value="PCS" @selected(old('unit') === 'PCS')>PCS</option>
                                            <option value="PACK" @selected(old('unit') === 'PACK')>PACK</option>
                                            <option value="BTG" @selected(old('unit') === 'BTG')>BTG</option>
                                        </select>

                                        @error('unit')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="barcode" class="form-label">
                                            Barcode
                                        </label>

                                        <input type="text" name="barcode" id="barcode"
                                            class="form-control crud-form-control
                                                @error('barcode') is-invalid @enderror"
                                            value="{{ old('barcode') }}" placeholder="Opsional" maxlength="255"
                                            autocomplete="off">

                                        @error('barcode')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>
                                                Kosongkan jika produk belum memiliki barcode.
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Harga dan Persediaan</h3>

                                    <p>
                                        Tentukan harga jual dan batas peringatan stok.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cost_price" class="form-label">
                                            Harga Modal
                                            <span class="crud-required">*</span>
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>

                                            <input type="number" name="cost_price" id="cost_price"
                                                class="form-control crud-form-control
                                                    @error('cost_price') is-invalid @enderror"
                                                value="{{ old('cost_price', 0) }}" min="0" step="1"
                                                required>
                                        </div>

                                        @error('cost_price')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="price" class="form-label">
                                            Harga Jual
                                            <span class="crud-required">*</span>
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>

                                            <input type="number" name="price" id="price"
                                                class="form-control crud-form-control
                                                    @error('price') is-invalid @enderror"
                                                value="{{ old('price', 0) }}" min="0" step="1" required>
                                        </div>

                                        @error('price')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="minimum_stock" class="form-label">
                                            Minimum Stok
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="number" name="minimum_stock" id="minimum_stock"
                                            class="form-control crud-form-control
                                                @error('minimum_stock') is-invalid @enderror"
                                            value="{{ old('minimum_stock', 0) }}" min="0" required>

                                        @error('minimum_stock')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Gambar dan Status</h3>

                                    <p>
                                        Gambar bersifat opsional dan membantu pencarian produk.
                                    </p>
                                </div>

                                <div class="product-image-upload">
                                    <div class="product-image-preview" id="productImagePreview">
                                        <span>P</span>
                                    </div>

                                    <div class="product-image-input">
                                        <label for="image" class="form-label">
                                            Gambar Produk
                                        </label>

                                        <input type="file" name="image" id="image"
                                            class="form-control crud-form-control
                                                @error('image') is-invalid @enderror"
                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                            data-image-preview-target="productImagePreview" data-max-size="2097152">

                                        @error('image')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>JPG, PNG, atau WEBP. Maksimal 2 MB.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="product-status-switch">
                                    <div>
                                        <strong>Produk Aktif</strong>

                                        <small>
                                            Produk aktif dapat dipilih pada halaman kasir.
                                        </small>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                            value="1" {{ old('is_active', true) ? 'checked' : '' }}>

                                        <label for="is_active" class="form-check-label">
                                            Aktif
                                        </label>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <aside class="product-preview-panel">
                            <span class="product-preview-label">
                                Pratinjau
                            </span>

                            <div class="product-preview-avatar" id="productPreviewAvatar">
                                P
                            </div>

                            <h3 id="productNamePreview">
                                NAMA PRODUK
                            </h3>

                            <p id="productSkuPreview">
                                SKU dibuat otomatis saat disimpan
                            </p>

                            <div class="product-preview-summary">
                                <div>
                                    <span>Harga Modal</span>
                                    <strong id="costPricePreview">Rp 0</strong>
                                </div>

                                <div>
                                    <span>Harga Jual</span>
                                    <strong id="sellingPricePreview">Rp 0</strong>
                                </div>

                                <div>
                                    <span>Laba Kotor</span>
                                    <strong id="grossProfitPreview">Rp 0</strong>
                                </div>

                                <div>
                                    <span>Margin Kotor</span>
                                    <strong id="grossMarginPreview">0%</strong>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>

                <div class="crud-form-footer">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>

                    <button type="submit" class="btn btn-primary js-submit-button"
                        data-loading-text="Menyimpan produk...">
                        Simpan Produk
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
