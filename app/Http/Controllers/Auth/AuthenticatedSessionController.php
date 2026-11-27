<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->is_active) {
            User::logoutSession($request);

            return back()
                ->withErrors([
                    'email' => 'Akun kamu sudah dinonaktifkan.',
                ])
                ->onlyInput('email');
        }

        // Single Active Session: kick any older device sessions for this user
        $user->revokeAllSessions($request->session()->getId());

        return redirect()->intended(route($user->homeRouteName()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        User::logoutSession($request);

        return redirect('/');
    }
}
