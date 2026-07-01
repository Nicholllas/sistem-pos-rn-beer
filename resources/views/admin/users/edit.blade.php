@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Form Edit User</h3>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

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
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}"
                        required>

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
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}"
                        required>

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

                    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required
                        @disabled($user->id === auth()->id())>
                        <option value="">Pilih Role</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>
                            Admin
                        </option>
                        <option value="cashier" @selected(old('role', $user->role) === 'cashier')>
                            Cashier
                        </option>
                    </select>

                    @if ($user->id === auth()->id())
                        <input type="hidden" name="role" value="{{ $user->role }}">

                        <small class="text-muted">
                            Role akun sendiri tidak bisa diubah dari halaman ini.
                        </small>
                    @else
                        <small class="text-muted">
                            Admin bisa akses semua fitur. Cashier nanti hanya untuk POS dan struk.
                        </small>
                    @endif

                    @error('role')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">

                    <input type="checkbox" name="is_active" id="is_active"
                        class="form-check-input @error('is_active') is-invalid @enderror" value="1"
                        @checked(old('is_active', $user->is_active) == '1') @disabled($user->id === auth()->id())>

                    <label for="is_active" class="form-check-label">
                        User Aktif
                    </label>

                    @if ($user->id === auth()->id())
                        <input type="hidden" name="is_active" value="1">

                        <br>
                        <small class="text-muted">
                            Akun sendiri tidak bisa dinonaktifkan.
                        </small>
                    @else
                        <br>
                        <small class="text-muted">
                            Jika nonaktif, user tidak boleh login.
                        </small>
                    @endif

                    @error('is_active')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <hr>

                <div class="alert alert-info mb-0">
                    Untuk mengganti password user, gunakan tombol
                    <strong>Reset Password</strong>
                    dari halaman daftar user.
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

@endsection
