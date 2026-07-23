@extends('layouts.admin')

@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Produk')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Form Tambah Produk
            </h3>
        </div>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card-body">

                <div class="alert alert-info">
                    Isi identitas produk dengan rapi. Nama produk dan SKU akan dibuat otomatis dari
                    <strong>Brand</strong>, <strong>Variant</strong>, <strong>Ukuran</strong>, dan
                    <strong>Satuan</strong>.

                    <div class="mt-2">
                        Stok awal produk otomatis bernilai 0. Penambahan stok dilakukan melalui menu
                        <strong>Stock In</strong> atau <strong>Pembelian Stok</strong>.
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">
                                Kategori
                            </label>

                            <select name="category_id" id="category_id"
                                class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Kategori --</option>

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
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand" class="form-label">
                                Brand
                            </label>

                            <input type="text" name="brand" id="brand"
                                class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}"
                                placeholder="Contoh: ANKER, INTISARI, BINTANG" required maxlength="100" autocomplete="off"
                                autofocus>

                            @error('brand')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Brand akan otomatis disimpan dalam huruf kapital.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="variant" class="form-label">
                                Variant / Jenis
                            </label>

                            <input type="text" name="variant" id="variant"
                                class="form-control @error('variant') is-invalid @enderror" value="{{ old('variant') }}"
                                placeholder="Contoh: BIR BOTOL, ANGGUR MERAH, KUNING" required maxlength="150"
                                autocomplete="off">

                            @error('variant')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Contoh: BIR BOTOL, BOTOL, KUNING, ORIGINAL, BLACK TEA.
                            </small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="size" class="form-label">
                                Ukuran
                            </label>

                            <input type="number" name="size" id="size"
                                class="form-control @error('size') is-invalid @enderror" value="{{ old('size') }}"
                                placeholder="Contoh: 620" min="1" required>

                            @error('size')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Contoh: 620, 330, 20.
                            </small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="unit" class="form-label">
                                Satuan
                            </label>

                            <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror"
                                required>
                                <option value="">-- Pilih --</option>
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

                            <small class="text-muted">
                                Contoh rokok: 20 BTG.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="barcode" class="form-label">
                        Barcode
                    </label>

                    <input type="text" name="barcode" id="barcode"
                        class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}"
                        placeholder="Opsional" maxlength="255" autocomplete="off">

                    @error('barcode')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Boleh dikosongkan kalau produk belum punya barcode.
                    </small>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cost_price" class="form-label">
                                Harga Modal
                            </label>

                            <input type="number" name="cost_price" id="cost_price"
                                class="form-control @error('cost_price') is-invalid @enderror"
                                value="{{ old('cost_price', 0) }}" min="0" step="1" required>

                            @error('cost_price')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="price" class="form-label">
                                Harga Jual
                            </label>

                            <input type="number" name="price" id="price"
                                class="form-control @error('price') is-invalid @enderror" value="{{ old('price', 0) }}"
                                min="0" step="1" required>

                            @error('price')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Harga jual tidak boleh lebih kecil dari harga modal.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="minimum_stock" class="form-label">
                                Minimum Stock
                            </label>

                            <input type="number" name="minimum_stock" id="minimum_stock"
                                class="form-control @error('minimum_stock') is-invalid @enderror"
                                value="{{ old('minimum_stock', 0) }}" min="0" required>

                            @error('minimum_stock')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Jika stok kurang dari atau sama dengan minimum stok, produk akan dianggap stok menipis.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">
                        Gambar Produk
                    </label>

                    <input type="file" name="image" id="image"
                        class="form-control @error('image') is-invalid @enderror" accept="image/*">

                    @error('image')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Opsional. Format gambar JPG, PNG, WEBP. Maksimal 2MB.
                    </small>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}>

                    <label for="is_active" class="form-check-label">
                        Produk aktif dan bisa dijual
                    </label>
                </div>

            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>

@endsection
