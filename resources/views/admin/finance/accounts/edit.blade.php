@extends('layouts.admin')

@section('title', 'Edit Akun Finance')
@section('page-title', 'Edit Akun Finance')
@section('page-subtitle', 'Perbarui identitas, mapping pembayaran, dan status akun Finance.')

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
                            <h2>{{ $account->name }}</h2>

                            <p>
                                Kode {{ $account->code }} dan data akun yang terhubung dengan transaksi Finance.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.finance.accounts.update', $account) }}" method="POST"
                        class="js-submit-form">
                        @csrf
                        @method('PUT')

                        <div class="crud-form-body">
                            @include('admin.finance.accounts._form')
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-outline-secondary">
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
