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
                    Konfirmasi Keamanan
                </div>
            </div>

            <!-- Elevated Clean White Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Konfirmasi Kata Sandi</h2>
                    <p>Ini adalah tindakan penting pada sistem. Silakan konfirmasi kata sandi Anda saat ini sebelum melanjutkan.</p>
                </div>

                @if (isset($errors) && $errors->any())
                    <div class="auth-alert-error" role="alert">
                        <strong>Konfirmasi Gagal:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
                    @csrf

                    <!-- Password Field -->
                    <div class="auth-field">
                        <label class="auth-label" for="password">Kata Sandi Saat Ini</label>
                        <div class="auth-input-group">
                            <input id="password" type="password" name="password" placeholder="••••••••" required
                                autocomplete="current-password" autofocus
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

                    <!-- Primary CTA Submit Button -->
                    <button type="submit" class="auth-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Konfirmasi dan Lanjutkan
                    </button>
                </form>

                <div class="auth-card-footer">
                    <div class="auth-security-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <span>Area Keamanan Tinggi &middot; RN Beer POS</span>
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
