@extends('layouts.admin')

@section('title', 'Edit Kategori Finance')
@section('page-title', 'Edit Kategori Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Edit {{ $category->name }}
            </h3>
        </div>

        <form action="{{ route('admin.finance.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('admin.finance.categories._form')
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

@endsection
