@extends('layouts.admin')

@section('title', 'Tambah Kategori Finance')
@section('page-title', 'Tambah Kategori Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Form Kategori Finance
            </h3>
        </div>

        <form action="{{ route('admin.finance.categories.store') }}" method="POST">
            @csrf

            <div class="card-body">
                @include('admin.finance.categories._form')
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>

@endsection
