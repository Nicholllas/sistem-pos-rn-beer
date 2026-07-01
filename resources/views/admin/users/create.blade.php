@extends('layouts.admin')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Form Tambah User</h3>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terjadi kesalahan.</strong>

                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama User <span class="text-danger">*</span>
                    </label>

                    <input type="text" name="name" id="name"
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                        placeholder="Contoh: KASIR 1" required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Nama akan otomatis disimpan huruf besar.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email <span class="text-danger">*</span>
                    </label>

                    <input type="email" name="email" id="email"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                        placeholder="contoh@email.com" required>

                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">
                        Role <span class="text-danger">*</span>
                    </label>

                    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" @selected(old('role') === 'admin')>
                            Admin
                        </option>
                        <option value="cashier" @selected(old('role') === 'cashier')>
                            Cashier
                        </option>
                    </select>

                    @error('role')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Admin bisa akses semua fitur. Cashier nanti hanya untuk POS dan struk.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password <span class="text-danger">*</span>
                    </label>

                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror" minlength="8" required>

                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Minimal 8 karakter.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">
                        Konfirmasi Password <span class="text-danger">*</span>
                    </label>

                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                        minlength="8" required>
                </div>

                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">

                    <input type="checkbox" name="is_active" id="is_active"
                        class="form-check-input @error('is_active') is-invalid @enderror" value="1"
                        @checked(old('is_active', '1') == '1')>

                    <label for="is_active" class="form-check-label">
                        User Aktif
                    </label>

                    @error('is_active')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <br>
                    <small class="text-muted">
                        User aktif bisa login ke sistem.
                    </small>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan User
                </button>
            </div>
        </form>
    </div>

@endsection
