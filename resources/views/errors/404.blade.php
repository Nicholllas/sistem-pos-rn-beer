<x-guest-layout>
    <div class="rn-login-page">
        {{-- Left: 404 Content --}}
        <div class="panel-left">
            <div class="brand">
                <div class="brand-icon">🍺</div>
                <div class="brand-name">RN <span>Beer</span></div>
                <div class="brand-sub">Point of Sale System</div>
            </div>

            <div class="form-wrap error-wrap">
                <div class="error-code">404</div>

                <div class="form-heading">Page not found</div>

                <div class="form-sub">
                    The page you are looking for does not exist or may have been moved.
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
            <div class="tagline-eyebrow">Lost Route</div>

            <h1 class="tagline">
                This page<br>
                went <em>missing.</em>
            </h1>

            <p class="tagline-body">
                No worries. Your POS data is still safe. Return to the dashboard
                or login page to continue managing your store operations.
            </p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">404</div>
                    <div class="stat-label">Not found</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">Safe</div>
                    <div class="stat-label">System data</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">RN</div>
                    <div class="stat-label">Beer POS</div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>