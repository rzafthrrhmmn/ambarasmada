<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $field = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $credentials['identity'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($request->ip());

            throw ValidationException::withMessages([
                'identity' => 'Kredensial yang dimasukkan salah.',
            ]);
        }

        if ($user->status === 'rejected') {
            Auth::logout();

            throw ValidationException::withMessages([
                'identity' => 'Akun Anda ditolak oleh Pembina. Hubungi Pembina untuk informasi lebih lanjut.',
            ]);
        }

        $request->session()->regenerate();
        Auth::login($user, $credentials['remember'] ?? false);

        $hasIntendedUrl = $request->session()->has('url.intended');
        $activationMessage = 'Email aktivasi Anda belum diverifikasi. Silakan periksa kotak masuk atau folder spam email Anda.';

        if (! $user->hasVerifiedEmail() && $hasIntendedUrl) {
            return redirect()->intended(route('verification.notice'))
                ->with('success', $activationMessage);
        }

        if ($user->status === 'pending') {
            return redirect()->route('pending-approval');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('success', $activationMessage);
        }

        return redirect()->intended(
            $user->role === 'Alumni'
                ? route('alumni.dashboard')
                : route('dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
