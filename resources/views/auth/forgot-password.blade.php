<x-guest-layout>
    <div class="rn-login-page">
        {{-- Left: Forgot Password Form --}}
        <div class="panel-left">
            <div class="brand">
                <div class="brand-icon">🍺</div>
                <div class="brand-name">RN <span>Beer</span></div>
                <div class="brand-sub">Point of Sale System</div>
            </div>

            <div class="form-wrap">
                <div class="form-heading">Reset password</div>

                <div class="form-sub">
                    Enter your email address and we will send you a password reset link.
                </div>

                {{-- Session Status --}}
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="field">
                        <label for="email">Email</label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@rnbeer.com"
                            required
                            autofocus
                        >

                        <x-input-error :messages="$errors->get('email')" class="input-error" />
                    </div>

                    <button type="submit" class="btn-signin">
                        Send Reset Link
                    </button>

                    <div class="auth-back-link">
                        <a href="{{ route('login') }}">
                            Back to login
                        </a>
                    </div>
                </form>
            </div>

            <div class="footer-note">© 2026 RN Beer · All rights reserved</div>
        </div>

        {{-- Decorative divider --}}
        <div class="panel-divider"></div>

        {{-- Right: Brand Panel --}}
        <div class="panel-right">
            <div class="tagline-eyebrow">Account Recovery</div>

            <h1 class="tagline">
                Get back<br>
                to your <em>dashboard.</em>
            </h1>

            <p class="tagline-body">
                Securely recover your RN Beer POS account and continue managing sales,
                stock, and store operations without losing access.
            </p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">Safe</div>
                    <div class="stat-label">Reset flow</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">&lt; <span>1m</span></div>
                    <div class="stat-label">Email delivery</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">24<span>/7</span></div>
                    <div class="stat-label">Access</div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>