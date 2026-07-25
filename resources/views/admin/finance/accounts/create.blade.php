@extends('layouts.admin')

@section('title', 'Tambah Akun Finance')
@section('page-title', 'Tambah Akun Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Form Akun Finance
            </h3>
        </div>

        <form action="{{ route('admin.finance.accounts.store') }}" method="POST">
            @csrf

            <div class="card-body">
                @include('admin.finance.accounts._form')
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>

@endsection
