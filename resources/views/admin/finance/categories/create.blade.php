@extends('layouts.admin')

@section('title', 'Tambah Kategori Finance')
@section('page-title', 'Tambah Kategori Finance')
@section('page-subtitle', 'Buat kategori baru untuk mengelompokkan pemasukan atau pengeluaran.')

@section('page-actions')
    <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    @include('admin.finance.partials.alerts')

    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <section class="crud-form-panel finance-form-panel">
                    <div class="crud-form-header">
                        <div>
                            <h2>Informasi Kategori Finance</h2>

                            <p>
                                Masukkan kode dan nama kategori yang konsisten serta mudah dikenali.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.finance.categories.store') }}" method="POST" class="js-submit-form">
                        @csrf

                        <div class="crud-form-body">
                            @include('admin.finance.categories._form')
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-outline-secondary">
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
