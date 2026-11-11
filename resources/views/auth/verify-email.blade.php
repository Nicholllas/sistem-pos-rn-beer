<x-guest-layout>
    <div class="auth-page">
        <div class="auth-wrapper">
            <!-- Brand Block -->
            <div class="auth-brand-block">
                <div class="auth-brand-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="16" x="2" y="4" rx="2" />
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                    </svg>
                </div>
                <h1 class="auth-brand-title">RN Beer <span>POS</span></h1>
                <div class="auth-brand-badge">
                    <span class="auth-live-dot" aria-hidden="true"></span>
                    Verifikasi Operator
                </div>
            </div>

            <!-- Elevated Clean White Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Verifikasi Alamat Email</h2>
                    <p>Terima kasih telah mendaftar. Sebelum mulai menggunakan sistem kasir, silakan verifikasi alamat email Anda melalui tautan yang kami kirimkan.</p>
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="auth-alert-success" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <span>Tautan verifikasi baru telah berhasil dikirimkan ke alamat email Anda.</span>
                    </div>
                @endif

                <div class="d-flex flex-column gap-2 mt-3">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="auth-submit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67" />
                            </svg>
                            Kirim Ulang Email Verifikasi
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="text-center">
                        @csrf
                        <button type="submit" class="auth-btn-outline w-100">
                            Keluar dari Akun
                        </button>
                    </form>
                </div>

                <div class="auth-card-footer">
                    <div class="auth-security-notice">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                        </svg>
                        <span>Integritas Akun Operator Toko</span>
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
