<x-guest-layout>
    <div class="rn-login-page">
        <div class="panel-left">
            <div class="brand">
                <div class="brand-icon">🍺</div>
                <div class="brand-name">RN <span>Beer</span></div>
                <div class="brand-sub">Point of Sale System</div>
            </div>

            <div class="form-wrap">
                <div class="form-heading">Welcome back</div>
                <div class="form-sub">Sign in to access your store dashboard</div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

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
                            autocomplete="username"
                        >
                        <x-input-error :messages="$errors->get('email')" class="input-error" />
                    </div>

                    <div class="field">
                        <div class="field-meta">
                            <label for="password">Password</label>

                            @if (Route::has('password.request'))
                                <a class="forgot-link" href="{{ route('password.request') }}">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <div class="field-row">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >

                            <button
                                type="button"
                                class="toggle-pw"
                                onclick="togglePassword()"
                                aria-label="Toggle password"
                            >
                                <svg id="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>

                                <svg id="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     style="display:none">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>

                        <x-input-error :messages="$errors->get('password')" class="input-error" />
                    </div>

                    <label for="remember_me" class="remember-row">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>Keep me signed in</span>
                    </label>

                    <button type="submit" class="btn-signin">
                        Sign In
                    </button>
                </form>
            </div>

            <div class="footer-note">© 2026 RN Beer · All rights reserved</div>
        </div>

        <div class="panel-divider"></div>

        <div class="panel-right">
            <div class="tagline-eyebrow">RN Beer · Est. 2024</div>

            <h1 class="tagline">
                Every pour<br>
                tracked <em>precisely.</em>
            </h1>

            <p class="tagline-body">
                Manage sales, inventory, and your team from one streamlined dashboard —
                built for the pace of a busy store.
            </p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">99<span>%</span></div>
                    <div class="stat-label">Uptime</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">&lt; <span>1s</span></div>
                    <div class="stat-label">Checkout speed</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">24<span>/7</span></div>
                    <div class="stat-label">Support</div>
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