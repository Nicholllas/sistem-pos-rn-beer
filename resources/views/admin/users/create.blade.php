@extends('layouts.admin')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')
@section('page-subtitle', 'Buat akun baru dan tentukan hak aksesnya dalam sistem.')

@section('page-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <div>
                            <h2>Informasi User Baru</h2>

                            <p>
                                Lengkapi identitas, akses, dan password awal user.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.users.store') }}" method="POST" class="js-submit-form">
                        @csrf

                        <div class="crud-form-body">
                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Identitas User</h3>

                                    <p>
                                        Nama dan email digunakan sebagai identitas akun.
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
                                            value="{{ old('name') }}" placeholder="Contoh: KASIR 1" required
                                            maxlength="255" autocomplete="off" autofocus
                                            data-character-counter="userNameCounter">

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
                                            value="{{ old('email') }}" placeholder="user@email.com" required
                                            autocomplete="off">

                                        @error('email')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>
                                                Email digunakan untuk login ke sistem.
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Hak Akses</h3>

                                    <p>
                                        Pilih role berdasarkan tanggung jawab user.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="role" class="form-label">
                                            Role
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="role" id="role"
                                            class="form-select crud-form-control
                                                @error('role') is-invalid @enderror"
                                            required>
                                            <option value="">
                                                Pilih role
                                            </option>

                                            <option value="admin" @selected(old('role') === 'admin')>
                                                Administrator
                                            </option>

                                            <option value="supervisor" @selected(old('role') === 'supervisor')>
                                                Supervisor
                                            </option>

                                            <option value="cashier" @selected(old('role') === 'cashier')>
                                                Kasir
                                            </option>
                                        </select>

                                        @error('role')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="supervisor_pin" class="form-label">
                                            PIN Otorisasi Supervisor
                                            <span class="text-muted">(Opsional, khusus Administrator &amp; Supervisor)</span>
                                        </label>

                                        <input type="password" name="supervisor_pin" id="supervisor_pin"
                                            class="form-control crud-form-control @error('supervisor_pin') is-invalid @enderror"
                                            placeholder="Contoh: 123456 (4-10 digit angka)" maxlength="10"
                                            inputmode="numeric" autocomplete="new-password">

                                        @error('supervisor_pin')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>
                                                Digunakan untuk mengotorisasi void transaksi, penyesuaian stok, atau tutup shift berselisih oleh kasir.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="user-role-guide">
                                    <div>
                                        <span class="user-role-badge is-admin">
                                            Administrator
                                        </span>

                                        <p>
                                            Akses penuh ke master data, keuangan,
                                            user, laporan, dan audit sistem.
                                        </p>
                                    </div>

                                    <div>
                                        <span class="user-role-badge is-supervisor">
                                            Supervisor
                                        </span>

                                        <p>
                                            Melihat ringkasan shift kasir, tutup paksa shift,
                                            dan memegang PIN otorisasi.
                                        </p>
                                    </div>

                                    <div>
                                        <span class="user-role-badge is-cashier">
                                            Kasir
                                        </span>

                                        <p>
                                            Difokuskan untuk transaksi POS, struk,
                                            dan tutup shift metode blind count.
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Password Awal</h3>

                                    <p>
                                        Gunakan password minimal 8 karakter.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">
                                            Password
                                            <span class="crud-required">*</span>
                                        </label>

                                        <div class="crud-password-field">
                                            <input type="password" name="password" id="password"
                                                class="form-control crud-form-control
                                                    @error('password') is-invalid @enderror"
                                                required minlength="8" autocomplete="new-password">

                                            <button type="button" class="crud-password-toggle"
                                                data-password-toggle="password">
                                                Lihat
                                            </button>
                                        </div>

                                        @error('password')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">
                                            Konfirmasi Password
                                            <span class="crud-required">*</span>
                                        </label>

                                        <div class="crud-password-field">
                                            <input type="password" name="password_confirmation" id="password_confirmation"
                                                class="form-control crud-form-control" required minlength="8"
                                                autocomplete="new-password" data-password-confirmation-for="password"
                                                data-password-match-target="passwordMatch">

                                            <button type="button" class="crud-password-toggle"
                                                data-password-toggle="password_confirmation">
                                                Lihat
                                            </button>
                                        </div>

                                        <small class="crud-password-match" id="passwordMatch">
                                            Password harus sama.
                                        </small>
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Status Akun</h3>

                                    <p>
                                        User aktif dapat login dan menggunakan sistem.
                                    </p>
                                </div>

                                <div class="crud-status-setting">
                                    <div>
                                        <strong>User Aktif</strong>

                                        <small>
                                            Nonaktifkan apabila akun belum boleh digunakan.
                                        </small>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active" value="0">

                                        <input type="checkbox" name="is_active" id="is_active"
                                            class="form-check-input
                                                @error('is_active') is-invalid @enderror"
                                            value="1" @checked(old('is_active', '1') == '1')>

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
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary js-submit-button"
                                data-loading-text="Menyimpan user...">
                                Simpan User
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
