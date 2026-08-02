<x-guest-layout>
    <div class="auth-page">
        <div class="auth-container">
            <!-- Left Panel - Forgot Password Form -->
            <div class="auth-panel auth-panel-left">
                <div class="auth-header">
                    <div class="auth-brand">
                        <span class="auth-brand-icon">🍺</span>
                        <div>
                            <h1 class="auth-brand-name">RN <span>Beer</span></h1>
                            <p class="auth-brand-sub">Point of Sale System</p>
                        </div>
                    </div>
                </div>

                <div class="auth-form-wrap">
                    <div class="auth-form-header">
                        <h2>Reset Password</h2>
                        <p>Masukkan alamat email Anda dan kami akan mengirimkan tautan untuk mereset password.</p>
                    </div>

                    {{-- Session Status --}}
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                        @csrf

                        <!-- Email Field -->
                        <div class="auth-field">
                            <label class="auth-label" for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                placeholder="you@rnbeer.com" required autofocus
                                class="auth-input @error('email') is-invalid @enderror">
                            <x-input-error :messages="$errors->get('email')" class="auth-error" />
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="auth-submit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                style="margin-right: 8px;">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            Kirim Tautan Reset
                        </button>

                        <!-- Back to Login -->
                        <div class="auth-back-link">
                            <a href="{{ route('login') }}" class="auth-back-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                                Kembali ke halaman login
                            </a>
                        </div>
                    </form>
                </div>

                <div class="auth-footer">
                    <p>© 2026 RN Beer · Seluruh hak cipta dilindungi</p>
                </div>
            </div>

            <!-- Right Panel - Account Recovery Hero -->
            <div class="auth-panel auth-panel-right">
                <div class="auth-hero">
                    <span class="auth-hero-badge">Pemulihan Akun</span>

                    <h2 class="auth-hero-title">
                        Kembali ke<br>
                        <em>dashboard Anda.</em>
                    </h2>

                    <p class="auth-hero-desc">
                        Pulihkan akun RN Beer POS Anda dengan aman dan lanjutkan mengelola
                        penjualan, stok, dan operasional toko tanpa kehilangan akses.
                    </p>

                    <div class="auth-stats">
                        <div class="auth-stat-item">
                            <span class="auth-stat-value auth-stat-value-text">Aman</span>
                            <span class="auth-stat-label">Proses reset</span>
                        </div>
                        <div class="auth-stat-item">
                            <span class="auth-stat-value">&lt; <span>1mnt</span></span>
                            <span class="auth-stat-label">Pengiriman email</span>
                        </div>
                        <div class="auth-stat-item">
                            <span class="auth-stat-value">24<span>/7</span></span>
                            <span class="auth-stat-label">Akses</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
