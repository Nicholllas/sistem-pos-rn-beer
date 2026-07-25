@extends('layouts.admin')

@section('title', 'Edit Akun Finance')
@section('page-title', 'Edit Akun Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Edit {{ $account->name }}
            </h3>
        </div>

        <form action="{{ route('admin.finance.accounts.update', $account) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('admin.finance.accounts._form')
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.finance.accounts.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

@endsection
