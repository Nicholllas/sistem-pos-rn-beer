<x-guest-layout>
    <div class="auth-page">
        <div class="auth-wrapper">
            <!-- Brand Block -->
            <div class="auth-brand-block">
                <div class="auth-brand-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <h1 class="auth-brand-title">RN Beer <span>POS</span></h1>
                <div class="auth-brand-badge">
                    <span class="auth-live-dot" aria-hidden="true"></span>
                    Pemulihan Kredensial
                </div>
            </div>

            <!-- Elevated Clean White Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Reset Kata Sandi</h2>
                    <p>Masukkan alamat email akun Anda. Kami akan mengirimkan tautan aman untuk mengatur ulang kata sandi.</p>
                </div>

                @if (session('status'))
                    <div class="auth-alert-success" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="auth-alert-error" role="alert">
                        <strong>Terjadi Kesalahan:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                    @csrf

                    <!-- Email Field -->
                    <div class="auth-field">
                        <label class="auth-label" for="email">Alamat Email Terdaftar</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                            placeholder="operator@rnbeer.com" required autofocus autocomplete="username"
                            class="auth-input @error('email') is-invalid @enderror">
                    </div>

                    <!-- Primary CTA Submit Button -->
                    <button type="submit" class="auth-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                        Kirim Tautan Reset
                    </button>

                    <!-- Back to Login -->
                    <a href="{{ route('login') }}" class="auth-btn-outline w-100">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                        Kembali ke Halaman Masuk
                    </a>
                </form>

                <div class="auth-card-footer">
                    <div class="auth-security-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                        </svg>
                        <span>Verifikasi Identitas Multi-Faktor</span>
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
