<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function pendingApproval(): RedirectResponse|Response
    {
        if (Auth::check() && Auth::user()->status === 'approved') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/PendingApproval');
    }

    public function showVerificationNotice(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $request->user()?->email,
            'nta' => $request->user()?->member?->nta,
            'linkExpiryMinutes' => AccountActivationNotification::expiryMinutes(),
        ]);
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu untuk menyelesaikan aktivasi akun.');
        }

        if (! $request->hasValidSignature()) {
            return redirect()->route('verification.notice')->with(
                'error',
                'Link verifikasi tidak valid atau sudah kedaluwarsa. Silakan tekan "Kirim Ulang Verifikasi" untuk meminta tautan baru.'
            );
        }

        $targetId = (int) $request->route('id');

        if ($targetId !== (int) $user->getKey()) {
            return redirect()->route('verification.notice')->with(
                'error',
                'Link verifikasi ini bukan milik akun yang sedang masuk. Silakan masuk menggunakan NTA Anda sendiri, lalu minta tautan baru.'
            );
        }

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect()->route('verification.notice')->with(
                'error',
                'Link verifikasi tidak cocok dengan email akun Anda. Silakan minta tautan baru.'
            );
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard'))->with('success', 'Email berhasil diverifikasi. Akun Anda sudah aktif.');
    }

    public function sendVerificationEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'))->with('success', 'Email Anda sudah terverifikasi.');
        }

        if ($user === null) {
            return back()->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $sent = $user->sendActivationEmail();

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Email aktivasi telah dikirim ulang ke '.$user->email.'. Silakan cek kotak masuk dan folder spam Anda.'
                : 'Gagal mengirim email aktivasi. Silakan coba lagi beberapa saat lagi atau hubungi Pembina.'
        );
    }

    public function showForgotPasswordForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255', 'exists:users,email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Tautan reset password telah dikirim ke email Anda. Silakan cek kotak masuk atau spam.');
        }

        throw ValidationException::withMessages([
            'email' => [match ($status) {
                Password::INVALID_USER => 'Email tersebut tidak terdaftar.',
                Password::RESET_THROTTLED => 'Terlalu banyak percobaan. Silakan coba lagi dalam beberapa saat.',
                default => 'Tautan reset password gagal dikirim. Silakan coba lagi.',
            }],
        ]);
    }

    public function showResetPasswordForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email'),
            'expiryMinutes' => (int) config('auth.passwords.users.expire', 60),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Password berhasil diperbarui. Silakan masuk dengan password baru.');
        }

        throw ValidationException::withMessages([
            'email' => [match ($status) {
                Password::INVALID_TOKEN => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
                Password::INVALID_USER => 'Email tersebut tidak terdaftar.',
                Password::RESET_THROTTLED => 'Terlalu banyak percobaan. Silakan coba lagi dalam beberapa saat.',
                default => 'Password gagal diperbarui. Silakan coba lagi.',
            }],
        ]);
    }

    public function resendActivationLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendActivationEmail();
        }

        return back()->with(
            'success',
            'Jika email tersebut terdaftar dan belum diverifikasi, tautan aktivasi baru telah dikirim. Silakan cek kotak masuk dan folder spam Anda.'
        );
    }
}
