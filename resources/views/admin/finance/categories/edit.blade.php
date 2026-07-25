@extends('layouts.admin')

@section('title', 'Edit Kategori Finance')
@section('page-title', 'Edit Kategori Finance')
@section('page-subtitle', 'Perbarui identitas, jenis, dan status kategori transaksi.')

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
                            <h2>{{ $category->name }}</h2>

                            <p>
                                Kode {{ $category->code }} dan pengaturan kategori transaksi Finance.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.finance.categories.update', $category) }}" method="POST"
                        class="js-submit-form">
                        @csrf
                        @method('PUT')

                        <div class="crud-form-body">
                            @include('admin.finance.categories._form')
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-outline-secondary">
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
