@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('page-subtitle', 'Perbarui identitas, role, dan status akun pengguna.')

@section('page-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    @php
        $isOwnAccount = $user->id === auth()->id();

        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    @endphp

    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <div>
                            <h2>{{ $user->name }}</h2>

                            <p>
                                {{ $user->email }} ·
                                {{ $user->role === 'admin' ? 'Administrator' : 'Kasir' }}
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="js-submit-form">
                        @csrf
                        @method('PUT')

                        <div class="crud-form-body">
                            @if ($isOwnAccount)
                                <div class="user-protected-notice">
                                    <span>!</span>

                                    <div>
                                        <strong>Akun yang sedang digunakan</strong>

                                        <p>
                                            Role dan status akun sendiri tidak dapat
                                            diubah untuk mencegah kehilangan akses.
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Identitas User</h3>

                                    <p>
                                        Nama dan email digunakan sebagai identitas login.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">
                                            Nama User
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="text" name="name" id="name"
                                            class="form-control crud-form-control
                                                @error('name') is-invalid @enderror"
                                            value="{{ old('name', $user->name) }}" required maxlength="255"
                                            autocomplete="off" autofocus data-character-counter="userNameCounter">

                                        @error('name')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>
                                                Nama disimpan dalam huruf kapital.
                                            </span>

                                            <span id="userNameCounter">
                                                0/255
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">
                                            Email
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="email" name="email" id="email"
                                            class="form-control crud-form-control
                                                @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" required autocomplete="off">

                                        @error('email')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Hak Akses</h3>

                                    <p>
                                        Tentukan fitur yang dapat diakses oleh user.
                                    </p>
                                </div>

                                <label for="role" class="form-label">
                                    Role
                                    <span class="crud-required">*</span>
                                </label>

                                <select name="role" id="role"
                                    class="form-select crud-form-control
                                        @error('role') is-invalid @enderror"
                                    required @disabled($isOwnAccount)>
                                    <option value="">
                                        Pilih role
                                    </option>

                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>
                                        Administrator
                                    </option>

                                    <option value="cashier" @selected(old('role', $user->role) === 'cashier')>
                                        Kasir
                                    </option>
                                </select>

                                @if ($isOwnAccount)
                                    <input type="hidden" name="role" value="{{ $user->role }}">
                                @endif

                                @error('role')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="user-role-guide">
                                    <div>
                                        <span class="user-role-badge is-admin">
                                            Administrator
                                        </span>

                                        <p>
                                            Memiliki akses penuh ke master data,
                                            inventory, pembelian, dan laporan.
                                        </p>
                                    </div>

                                    <div>
                                        <span class="user-role-badge is-cashier">
                                            Kasir
                                        </span>

                                        <p>
                                            Difokuskan pada POS, struk, dan riwayat
                                            transaksi penjualan.
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Status Akun</h3>

                                    <p>
                                        User nonaktif tidak dapat login ke sistem.
                                    </p>
                                </div>

                                <div class="crud-status-setting">
                                    <div>
                                        <strong>User Aktif</strong>

                                        <small>
                                            Status menentukan apakah user boleh login.
                                        </small>
                                    </div>

                                    <div class="form-check form-switch">
                                        @if ($isOwnAccount)
                                            <input type="hidden" name="is_active" value="1">

                                            <input type="checkbox" id="is_active" class="form-check-input" checked disabled>
                                        @else
                                            <input type="hidden" name="is_active" value="0">

                                            <input type="checkbox" name="is_active" id="is_active"
                                                class="form-check-input
                                                    @error('is_active') is-invalid @enderror"
                                                value="1" @checked(old('is_active', $user->is_active) == '1')>
                                        @endif

                                        <label for="is_active" class="form-check-label">
                                            Aktif
                                        </label>
                                    </div>
                                </div>

                                @error('is_active')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </section>

                            <div class="crud-info-box">
                                <strong>Perubahan password</strong>

                                <p>
                                    Password tidak diubah dari halaman ini.
                                    Gunakan tombol Reset Password pada daftar user.
                                </p>
                            </div>

                            <div class="crud-audit-panel">
                                <div class="crud-audit-header">
                                    <h3>Riwayat User</h3>

                                    <p>
                                        Informasi pencatatan dan perubahan akun.
                                    </p>
                                </div>

                                <div class="crud-audit-grid">
                                    <div class="crud-audit-item">
                                        <span>ID User</span>

                                        <strong>
                                            #{{ $user->id }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Status Saat Ini</span>

                                        <strong>
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Dibuat Pada</span>

                                        <strong>
                                            {{ $user->created_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah</span>

                                        <strong>
                                            {{ $user->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
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
