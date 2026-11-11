<x-guest-layout>
    <div class="auth-page">
        <div class="auth-wrapper">
            <!-- Brand Block -->
            <div class="auth-brand-block">
                <div class="auth-brand-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <line x1="19" y1="8" x2="19" y2="14" />
                        <line x1="22" y1="11" x2="16" y2="11" />
                    </svg>
                </div>
                <h1 class="auth-brand-title">RN Beer <span>POS</span></h1>
                <div class="auth-brand-badge">
                    <span class="auth-live-dot" aria-hidden="true"></span>
                    Pendaftaran Operator
                </div>
            </div>

            <!-- Elevated Clean White Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Buat Akun Operator</h2>
                    <p>Daftarkan akun baru untuk mengelola operasional kasir dan manajemen barang.</p>
                </div>

                @if (isset($errors) && $errors->any())
                    <div class="auth-alert-error" role="alert">
                        <strong>Registrasi Gagal:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ Route::has('register') ? route('register') : '#' }}" class="auth-form">
                    @csrf

                    <!-- Name -->
                    <div class="auth-field">
                        <label for="name" class="auth-label">Nama Lengkap</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                            placeholder="Nama Lengkap Operator" required autofocus autocomplete="name"
                            class="auth-input @error('name') is-invalid @enderror">
                    </div>

                    <!-- Email -->
                    <div class="auth-field">
                        <label for="email" class="auth-label">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                            placeholder="operator@rnbeer.com" required autocomplete="username"
                            class="auth-input @error('email') is-invalid @enderror">
                    </div>

                    <!-- Password -->
                    <div class="auth-field">
                        <label for="password" class="auth-label">Kata Sandi</label>
                        <input id="password" type="password" name="password" placeholder="••••••••" required
                            autocomplete="new-password"
                            class="auth-input @error('password') is-invalid @enderror">
                    </div>

                    <!-- Confirm Password -->
                    <div class="auth-field">
                        <label for="password_confirmation" class="auth-label">Konfirmasi Kata Sandi</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                            placeholder="••••••••" required autocomplete="new-password"
                            class="auth-input @error('password_confirmation') is-invalid @enderror">
                    </div>

                    <!-- Primary CTA Submit Button -->
                    <button type="submit" class="auth-submit">
                        Daftarkan Akun
                    </button>

                    <div style="text-align: center; margin-top: 10px;">
                        <a href="{{ route('login') }}" class="auth-link">
                            Sudah memiliki akun? Masuk ke sistem
                        </a>
                    </div>
                </form>

                <div class="auth-card-footer">
                    <div class="auth-security-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <span>Akses Terkendali Sistem POS</span>
                    </div>
                </div>
            </div>

            <!-- Page Outer Footer -->
            <footer class="auth-page-footer">
                <p>&copy; 2026 RN Beer POS. Hak cipta dilindungi undang-undang.</p>
            </footer>
        </div>
    </div>
</x-guest-layout>
