<x-guest-layout>
    <div class="rn-login-page">
        {{-- Left: 403 Content --}}
        <div class="panel-left">
            <div class="brand">
                <div class="brand-icon">🍺</div>
                <div class="brand-name">RN <span>Beer</span></div>
                <div class="brand-sub">Point of Sale System</div>
            </div>

            <div class="form-wrap error-wrap">
                <div class="error-code">403</div>

                <div class="form-heading">Access forbidden</div>

                <div class="form-sub">
                    Kamu tidak memiliki akses ke halaman ini. Silakan kembali ke halaman yang sesuai dengan role akun
                    kamu.
                </div>

                <div class="error-actions">
                    @auth
                        @if (auth()->user()->role === 'cashier')
                            <a href="{{ route('admin.sales.pos') }}" class="btn-signin error-btn">
                                Back to POS
                            </a>
                        @else
                            <a href="{{ route('admin.dashboard') }}" class="btn-signin error-btn">
                                Back to Dashboard
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-signin error-btn">
                            Back to Login
                        </a>
                    @endauth
                </div>
            </div>

            <div class="footer-note">© 2026 RN Beer · All rights reserved</div>
        </div>

        {{-- Decorative divider --}}
        <div class="panel-divider"></div>

        {{-- Right: Brand Panel --}}
        <div class="panel-right">
            <div class="tagline-eyebrow">Restricted Area</div>

            <h1 class="tagline">
                Access<br>
                is <em>restricted.</em>
            </h1>

            <p class="tagline-body">
                Halaman ini hanya bisa diakses oleh role tertentu. Lagian mau cari apaan sih di sini?
            </p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">403</div>
                    <div class="stat-label">Forbidden</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">Role</div>
                    <div class="stat-label">Protected</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">RN</div>
                    <div class="stat-label">Beer POS</div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
