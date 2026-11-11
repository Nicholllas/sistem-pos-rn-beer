<x-guest-layout>
    <div class="auth-page">
        <div class="auth-wrapper">
            <!-- Brand Block (Classy Corporate POS Mark) -->
            <div class="auth-brand-block">
                <div class="auth-brand-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                        <line x1="8" y1="21" x2="16" y2="21" />
                        <line x1="12" y1="17" x2="12" y2="21" />
                        <circle cx="8" cy="8" r="1" />
                        <circle cx="12" cy="8" r="1" />
                        <circle cx="16" cy="8" r="1" />
                    </svg>
                </div>
                <h1 class="auth-brand-title">RN Beer <span>POS</span></h1>
                <div class="auth-brand-badge">
                    <span class="auth-live-dot" aria-hidden="true"></span>
                    Terminal Kasir &amp; Manajemen
                </div>
            </div>

            <!-- Elevated Clean White Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Masuk ke Sistem</h2>
                    <p>Masukkan kredensial Anda untuk mengakses terminal kasir dan data operasional toko.</p>
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
                        <strong>Gagal Masuk:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf

                    <!-- Email Field -->
                    <div class="auth-field">
                        <label class="auth-label" for="email">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                            placeholder="operator@rnbeer.com" required autofocus autocomplete="username"
                            class="auth-input @error('email') is-invalid @enderror">
                    </div>

                    <!-- Password Field -->
                    <div class="auth-field">
                        <div class="auth-field-header">
                            <label class="auth-label" for="password">Kata Sandi</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="auth-link">
                                    Lupa kata sandi?
                                </a>
                            @endif
                        </div>

                        <div class="auth-input-group">
                            <input id="password" type="password" name="password" placeholder="••••••••" required
                                autocomplete="current-password"
                                class="auth-input @error('password') is-invalid @enderror">
                            <button type="button" class="auth-input-btn" onclick="togglePassword()"
                                aria-label="Tampilkan atau sembunyikan kata sandi">
                                <svg id="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg id="icon-eye-off" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" style="display:none" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <label for="remember_me" class="auth-checkbox">
                        <input id="remember_me" type="checkbox" name="remember" class="auth-checkbox-input">
                        <span class="auth-checkbox-label">Ingat sesi saya di perangkat kasir ini</span>
                    </label>

                    <!-- Primary CTA Submit Button -->
                    <button type="submit" class="auth-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        Masuk ke Sistem
                    </button>
                </form>

                <div class="auth-card-footer">
                    <div class="auth-security-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <span>Sesi Terenkripsi &middot; Sistem POS RN Beer</span>
                    </div>
                </div>
            </div>

            <!-- Page Outer Footer -->
            <footer class="auth-page-footer">
                <p>&copy; 2026 RN Beer POS. Hak cipta dilindungi undang-undang.</p>
            </footer>
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
