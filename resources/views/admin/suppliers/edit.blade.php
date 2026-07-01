@extends('layouts.admin')

@section('title', 'Edit Supplier')
@section('page-title', 'Edit Supplier')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Form Edit Supplier
            </h3>
        </div>

        <form action="{{ route('admin.suppliers.update', $supplier) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Supplier <span class="text-danger">*</span>
                    </label>

                    <input type="text" name="name" id="name"
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name) }}"
                        required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Nama supplier akan otomatis disimpan huruf besar.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">
                        No. Telepon / WhatsApp
                    </label>

                    <input type="text" name="phone" id="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $supplier->phone) }}">

                    @error('phone')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email
                    </label>

                    <input type="email" name="email" id="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $supplier->email) }}">

                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">
                        Alamat
                    </label>

                    <textarea name="address" id="address" rows="4" class="form-control @error('address') is-invalid @enderror">{{ old('address', $supplier->address) }}</textarea>

                    @error('address')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">

                    <input type="checkbox" name="is_active" id="is_active"
                        class="form-check-input @error('is_active') is-invalid @enderror" value="1"
                        @checked(old('is_active', $supplier->is_active) == '1')>

                    <label for="is_active" class="form-check-label">
                        Supplier Aktif
                    </label>

                    @error('is_active')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                    <br>
                    <small class="text-muted">
                        Jika nonaktif, supplier tidak direkomendasikan untuk dipakai di transaksi pembelian stok.
                    </small>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            Dibuat oleh:
                            <strong>{{ $supplier->createdBy?->name ?? '-' }}</strong>
                            <br>
                            Pada:
                            <strong>{{ $supplier->created_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                        </small>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted">
                            Update terakhir oleh:
                            <strong>{{ $supplier->updatedBy?->name ?? '-' }}</strong>
                            <br>
                            Pada:
                            <strong>{{ $supplier->updated_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

@endsection
