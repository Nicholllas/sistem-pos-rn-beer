@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')
@section('page-subtitle', 'Perbarui identitas, harga, gambar, dan status produk.')

@section('page-actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <section class="crud-form-panel">
            <div class="crud-form-header">
                <span class="crud-form-avatar">
                    {{ strtoupper(substr($product->name, 0, 1)) }}
                </span>

                <div>
                    <h2>{{ $product->name }}</h2>

                    <p>
                        SKU {{ $product->sku }} ·
                        Stok {{ number_format($product->stock, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data"
                class="js-submit-form">
                @csrf
                @method('PUT')

                <div class="crud-form-body">
                    <div class="product-stock-card">
                        <div>
                            <span>Stok Saat Ini</span>

                            <strong>
                                {{ number_format($product->stock, 0, ',', '.') }}
                            </strong>

                            <small>
                                Stok tidak dapat diubah dari halaman produk.
                            </small>
                        </div>

                        <div class="product-stock-actions">
                            <a href="{{ route('admin.inventory.stock-in') }}" class="btn btn-sm btn-outline-primary">
                                Stok Masuk
                            </a>

                            <a href="{{ route('admin.inventory.adjustment') }}" class="btn btn-sm btn-outline-secondary">
                                Penyesuaian
                            </a>
                        </div>
                    </div>

                    <div class="product-form-layout">
                        <div class="product-form-main">
                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Identitas Produk</h3>

                                    <p>
                                        Perubahan data akan memperbarui nama dan SKU produk.
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
                                            <option value="">Pilih kategori</option>

                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
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
                                            value="{{ old('brand', $product->brand) }}" required maxlength="100"
                                            autocomplete="off" autofocus data-character-counter="brandCounter">

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
                                            value="{{ old('variant', $product->variant) }}" required maxlength="150"
                                            autocomplete="off" data-character-counter="variantCounter">

                                        @error('variant')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>Contoh: BOTOL, ORIGINAL, BLACK TEA.</span>
                                            <span id="variantCounter">0/150</span>
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
                                            value="{{ old('size', $product->size) }}" min="1" required>

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
                                            <option value="ML" @selected(old('unit', $product->unit) === 'ML')>ML</option>
                                            <option value="L" @selected(old('unit', $product->unit) === 'L')>L</option>
                                            <option value="GR" @selected(old('unit', $product->unit) === 'GR')>GR</option>
                                            <option value="PCS" @selected(old('unit', $product->unit) === 'PCS')>PCS</option>
                                            <option value="PACK" @selected(old('unit', $product->unit) === 'PACK')>PACK</option>
                                            <option value="BTG" @selected(old('unit', $product->unit) === 'BTG')>BTG</option>
                                        </select>

                                        @error('unit')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="barcode" class="form-label">
                                            Barcode
                                        </label>

                                        <input type="text" name="barcode" id="barcode"
                                            class="form-control crud-form-control
                                                @error('barcode') is-invalid @enderror"
                                            value="{{ old('barcode', $product->barcode) }}" placeholder="Opsional"
                                            maxlength="255" autocomplete="off">

                                        @error('barcode')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Harga dan Persediaan</h3>

                                    <p>
                                        Harga jual tidak boleh lebih kecil dari harga modal.
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
                                                value="{{ old('cost_price', $product->cost_price) }}" min="0"
                                                step="1" required>
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
                                                value="{{ old('price', $product->price) }}" min="0"
                                                step="1" required>
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
                                            value="{{ old('minimum_stock', $product->minimum_stock) }}"
                                            min="0" required>

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
                                        Kosongkan gambar jika tidak ingin menggantinya.
                                    </p>
                                </div>

                                <div class="product-image-upload">
                                    <div class="product-image-preview" id="productImagePreview">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}"
                                                alt="{{ $product->name }}">
                                        @else
                                            <span>
                                                {{ strtoupper(substr($product->name, 0, 1)) }}
                                            </span>
                                        @endif
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
                                            Produk aktif dapat digunakan pada halaman kasir.
                                        </small>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                            value="1"
                                            {{ old('is_active', $product->is_active) ? 'checked' : '' }}>

                                        <label for="is_active" class="form-check-label">
                                            Aktif
                                        </label>
                                    </div>
                                </div>
                            </section>

                            <div class="crud-audit-panel">
                                <div class="crud-audit-header">
                                    <h3>Riwayat Produk</h3>

                                    <p>
                                        Informasi pembuatan, perubahan data, dan stok.
                                    </p>
                                </div>

                                <div class="crud-audit-grid">
                                    <div class="crud-audit-item">
                                        <span>Nama Produk Saat Ini</span>
                                        <strong>{{ $product->name }}</strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>SKU Saat Ini</span>
                                        <strong>{{ $product->sku }}</strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Dibuat Oleh</span>

                                        <strong>
                                            {{ $product->createdBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Dibuat Pada</span>

                                        <strong>
                                            {{ $product->created_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah Oleh</span>

                                        <strong>
                                            {{ $product->updatedBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah</span>

                                        <strong>
                                            {{ $product->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Stok Terakhir Diubah Oleh</span>

                                        <strong>
                                            {{ $product->lastStockUpdatedBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Stok Terakhir Diubah</span>

                                        <strong>
                                            {{ $product->last_stock_updated_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <aside class="product-preview-panel">
                            <span class="product-preview-label">
                                Pratinjau
                            </span>

                            <div class="product-preview-avatar" id="productPreviewAvatar">
                                {{ strtoupper(substr($product->name, 0, 1)) }}
                            </div>

                            <h3 id="productNamePreview">
                                {{ $product->name }}
                            </h3>

                            <p id="productSkuPreview">
                                SKU {{ $product->sku }}
                            </p>

                            <div class="product-preview-summary">
                                <div>
                                    <span>Harga Modal</span>

                                    <strong id="costPricePreview">
                                        Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                    </strong>
                                </div>

                                <div>
                                    <span>Harga Jual</span>

                                    <strong id="sellingPricePreview">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </strong>
                                </div>

                                <div>
                                    <span>Laba Kotor</span>

                                    <strong id="grossProfitPreview">
                                        Rp
                                        {{ number_format(max($product->price - $product->cost_price, 0), 0, ',', '.') }}
                                    </strong>
                                </div>

                                <div>
                                    <span>Margin Kotor</span>

                                    <strong id="grossMarginPreview">
                                        {{ $product->price > 0
                                            ? number_format((($product->price - $product->cost_price) / $product->price) * 100, 1, ',', '.')
                                            : 0 }}%
                                    </strong>
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
                        data-loading-text="Menyimpan perubahan...">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
