<x-guest-layout>
    <div class="auth-page">
        <div class="auth-container">
            <!-- Left Panel - Form -->
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
                        <h2>Selamat Datang Kembali</h2>
                        <p>Masuk untuk mengakses dashboard toko Anda</p>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="auth-form">
                        @csrf

                        <!-- Email Field -->
                        <div class="auth-field">
                            <label class="auth-label" for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                placeholder="you@rnbeer.com" required autofocus autocomplete="username"
                                class="auth-input @error('email') is-invalid @enderror">
                            <x-input-error :messages="$errors->get('email')" class="auth-error" />
                        </div>

                        <!-- Password Field -->
                        <div class="auth-field">
                            <div class="auth-field-header">
                                <label class="auth-label" for="password">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="auth-link">
                                        Lupa password?
                                    </a>
                                @endif
                            </div>

                            <div class="auth-input-group">
                                <input id="password" type="password" name="password" placeholder="••••••••" required
                                    autocomplete="current-password"
                                    class="auth-input @error('password') is-invalid @enderror">
                                <button type="button" class="auth-input-btn" onclick="togglePassword()"
                                    aria-label="Toggle password visibility">
                                    <svg id="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg id="icon-eye-off" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" style="display:none">
                                        <path
                                            d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                        <path
                                            d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                        <line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="auth-error" />
                        </div>

                        <!-- Remember Me -->
                        <label for="remember_me" class="auth-checkbox">
                            <input id="remember_me" type="checkbox" name="remember" class="auth-checkbox-input">
                            <span class="auth-checkbox-label">Tetap masuk</span>
                        </label>

                        <!-- Submit Button -->
                        <button type="submit" class="auth-submit">
                            Masuk
                        </button>
                    </form>
                </div>

                <div class="auth-footer">
                    <p>© 2026 RN Beer · Seluruh hak cipta dilindungi</p>
                </div>
            </div>

            <!-- Right Panel - Hero -->
            <div class="auth-panel auth-panel-right">
                <div class="auth-hero">
                    <span class="auth-hero-badge">RN Beer · Est. 2024</span>

                    <h2 class="auth-hero-title">
                        Setiap tuangan<br>
                        tercatat <em>dengan presisi.</em>
                    </h2>

                    <p class="auth-hero-desc">
                        Kelola penjualan, inventaris, dan tim Anda dari satu dashboard
                        yang efisien - dibangun untuk ritme toko yang sibuk.
                    </p>

                    <div class="auth-stats">
                        <div class="auth-stat-item">
                            <span class="auth-stat-value">99<span>%</span></span>
                            <span class="auth-stat-label">Uptime</span>
                        </div>
                        <div class="auth-stat-item">
                            <span class="auth-stat-value">&lt; <span>1dtk</span></span>
                            <span class="auth-stat-label">Kecepatan checkout</span>
                        </div>
                        <div class="auth-stat-item">
                            <span class="auth-stat-value">24<span>/7</span></span>
                            <span class="auth-stat-label">Dukungan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function togglePassword() {
                const input = document.getElementById('password');
                const on = document.getElementById('icon-eye');
                const off = document.getElementById('icon-eye-off');

                if (input.type === 'password') {
                    input.type = 'text';
                    on.style.display = 'none';
                    off.style.display = 'inline';
                } else {
                    input.type = 'password';
                    on.style.display = 'inline';
                    off.style.display = 'none';
                }
            }
        </script>
    @endpush
</x-guest-layout>

