@extends('layouts.admin')

@section('title', 'Tambah Kategori')
@section('page-title', 'Tambah Kategori')
@section('page-subtitle', 'Buat kategori baru untuk mengelompokkan produk.')

@section('page-actions')
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <span class="crud-form-avatar">
                            K
                        </span>

                        <div>
                            <h2>Informasi Kategori</h2>

                            <p>
                                Masukkan nama kategori yang singkat, konsisten,
                                dan mudah dipahami.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.categories.store') }}" method="POST" class="js-submit-form">
                        @csrf

                        <div class="crud-form-body">
                            <div class="crud-form-group">
                                <label for="name" class="form-label">
                                    Nama Kategori
                                    <span class="crud-required">*</span>
                                </label>

                                <input type="text" name="name" id="name"
                                    class="form-control crud-form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="Contoh: BEER, SOJU, SNACK" required
                                    maxlength="255" autocomplete="off" autofocus
                                    data-character-counter="categoryNameCounter">

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="crud-field-help">
                                    <span>
                                        Nama digunakan pada data produk dan pencarian POS.
                                    </span>

                                    <span id="categoryNameCounter">
                                        0/255
                                    </span>
                                </div>
                            </div>

                            <div class="crud-info-box">
                                <strong>Tips penamaan</strong>

                                <p>
                                    Hindari kategori dengan arti yang sama,
                                    misalnya “Beer” dan “Bir”.
                                </p>
                            </div>
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary js-submit-button"
                                data-loading-text="Menyimpan kategori...">
                                Simpan Kategori
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
