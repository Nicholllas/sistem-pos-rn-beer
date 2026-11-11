<x-guest-layout>
    <div class="auth-page">
        <div class="auth-wrapper">
            <!-- Brand Block -->
            <div class="auth-brand-block">
                <div class="auth-brand-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 5 4 4" />
                        <path d="M13 7 8.7 11.3a2 2 0 0 0-.6 1.4V16h3.3a2 2 0 0 0 1.4-.6L17 11" />
                        <circle cx="18" cy="6" r="3" />
                    </svg>
                </div>
                <h1 class="auth-brand-title">RN Beer <span>POS</span></h1>
                <div class="auth-brand-badge">
                    <span class="auth-live-dot" aria-hidden="true"></span>
                    Pengaturan Sandi Baru
                </div>
            </div>

            <!-- Elevated Clean White Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Kata Sandi Baru</h2>
                    <p>Silakan buat kata sandi baru yang aman untuk akun operator terminal Anda.</p>
                </div>

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

                <form method="POST" action="{{ route('password.store') }}" class="auth-form">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div class="auth-field">
                        <label class="auth-label" for="email">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                            required autofocus autocomplete="username"
                            class="auth-input @error('email') is-invalid @enderror">
                    </div>

                    <!-- Password -->
                    <div class="auth-field">
                        <label class="auth-label" for="password">Kata Sandi Baru</label>
                        <div class="auth-input-group">
                            <input id="password" type="password" name="password" placeholder="••••••••" required
                                autocomplete="new-password"
                                class="auth-input @error('password') is-invalid @enderror">
                            <button type="button" class="auth-input-btn" onclick="togglePasswordVisibility('password', 'eye-1', 'eye-off-1')"
                                aria-label="Tampilkan atau sembunyikan kata sandi baru">
                                <svg id="eye-1" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg id="eye-off-1" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" style="display:none" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="auth-field">
                        <label class="auth-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                        <div class="auth-input-group">
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                placeholder="••••••••" required autocomplete="new-password"
                                class="auth-input @error('password_confirmation') is-invalid @enderror">
                            <button type="button" class="auth-input-btn" onclick="togglePasswordVisibility('password_confirmation', 'eye-2', 'eye-off-2')"
                                aria-label="Tampilkan atau sembunyikan konfirmasi kata sandi">
                                <svg id="eye-2" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg id="eye-off-2" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" style="display:none" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Primary CTA Submit Button -->
                    <button type="submit" class="auth-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                        Simpan Kata Sandi Baru
                    </button>

                    <!-- Back to Login -->
                    <a href="{{ route('login') }}" class="auth-btn-outline w-100">
                        Kembali ke Halaman Masuk
                    </a>
                </form>

                <div class="auth-card-footer">
                    <div class="auth-security-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                        </svg>
                        <span>Proteksi Kredensial Hashing Bcrypt</span>
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
            function togglePasswordVisibility(inputId, eyeId, eyeOffId) {
                const input = document.getElementById(inputId);
                const on = document.getElementById(eyeId);
                const off = document.getElementById(eyeOffId);

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
