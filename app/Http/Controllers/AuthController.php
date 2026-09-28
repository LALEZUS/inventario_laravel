<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use App\Services\AuditLogger;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $key = strtolower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'username' => 'Demasiados intentos. Espera '.RateLimiter::availableIn($key).' segundos.',
            ])->onlyInput('username');
        }

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors([
                'username' => 'Usuario o contrasena incorrectos.',
            ])->onlyInput('username');
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $audit->record('login', 'users', $user->id, null, ['username' => $user->username], $user);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $audit->record('logout', 'users', $user?->id, ['username' => $user?->username], null, $user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
