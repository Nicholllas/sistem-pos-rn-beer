@extends('layouts.admin')

@section('title', 'Tambah Akun Finance')
@section('page-title', 'Tambah Akun Finance')
@section('page-subtitle', 'Buat akun kas, bank, QRIS, atau dompet digital baru.')

@section('page-actions')
    <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-outline-secondary">
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
                            <h2>Informasi Akun Finance</h2>

                            <p>
                                Lengkapi data akun yang akan digunakan untuk menyimpan dan memindahkan dana.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.finance.accounts.store') }}" method="POST" class="js-submit-form">
                        @csrf

                        <div class="crud-form-body">
                            @include('admin.finance.accounts._form')
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary js-submit-button"
                                data-loading-text="Menyimpan akun...">
                                Simpan Akun
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
