@extends('layouts.admin')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Form Edit Kategori</h3>
        </div>

        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Kategori
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $category->name) }}"
                        placeholder="Contoh: BEER, SOJU, SNACK"
                        required
                        maxlength="255"
                        autocomplete="off"
                        autofocus
                    >

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Nama kategori akan otomatis disimpan dalam huruf kapital.
                    </small>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">
                            Dibuat oleh
                        </small>

                        <strong>
                            {{ $category->createdBy?->name ?? '-' }}
                        </strong>
                    </div>

                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">
                            Terakhir diubah oleh
                        </small>

                        <strong>
                            {{ $category->updatedBy?->name ?? '-' }}
                        </strong>
                    </div>

                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">
                            Tanggal dibuat
                        </small>

                        <strong>
                            {{ $category->created_at?->format('d M Y H:i') ?? '-' }}
                        </strong>
                    </div>

                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">
                            Terakhir diubah
                        </small>

                        <strong>
                            {{ $category->updated_at?->format('d M Y H:i') ?? '-' }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.categories.index') }}"
                   class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    Update
                </button>
            </div>
        </form>
    </div>
@endsection