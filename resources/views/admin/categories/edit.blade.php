@extends('layouts.admin')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')
@section('page-subtitle', 'Perbarui nama dan informasi kategori produk.')

@section('page-actions')
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <span class="crud-form-avatar">
                            {{ strtoupper(mb_substr($category->name, 0, 1)) }}
                        </span>

                        <div>
                            <h2>{{ $category->name }}</h2>

                            <p>
                                Perubahan nama akan langsung digunakan oleh
                                produk yang terhubung.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="js-submit-form">
                        @csrf
                        @method('PUT')

                        <div class="crud-form-body">
                            <div class="crud-form-group">
                                <label for="name" class="form-label">
                                    Nama Kategori
                                    <span class="crud-required">*</span>
                                </label>

                                <input type="text" name="name" id="name"
                                    class="form-control crud-form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $category->name) }}" placeholder="Contoh: BEER, SOJU, SNACK"
                                    required maxlength="255" autocomplete="off" autofocus
                                    data-character-counter="categoryNameCounter">

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="crud-field-help">
                                    <span>
                                        Nama kategori disimpan dalam format huruf kapital.
                                    </span>

                                    <span id="categoryNameCounter">
                                        0/255
                                    </span>
                                </div>
                            </div>

                            <div class="crud-audit-panel">
                                <div class="crud-audit-header">
                                    <h3>Riwayat Data</h3>

                                    <p>
                                        Informasi pembuatan dan perubahan kategori.
                                    </p>
                                </div>

                                <div class="crud-audit-grid">
                                    <div class="crud-audit-item">
                                        <span>Dibuat Oleh</span>

                                        <strong>
                                            {{ $category->createdBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Dibuat Pada</span>

                                        <strong>
                                            {{ $category->created_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah Oleh</span>

                                        <strong>
                                            {{ $category->updatedBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah</span>

                                        <strong>
                                            {{ $category->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
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
        </div>
    </div>
@endsection
