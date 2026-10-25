<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function showLoginForm(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login', [
            'ambalan' => $this->ambalanData(),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $identity = $request->validated('identity');
        $password = $request->validated('password');
        $remember = $request->validated('remember') ?? false;

        // Tentukan apakah identitas adalah email atau username
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $identity)->first();

        // Validasi kredensial
        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($request->ip());

            throw ValidationException::withMessages([
                'identity' => 'Kredensial yang dimasukkan salah.',
            ]);
        }

        // Akun yang ditolak tidak boleh login
        if ($user->status === 'rejected') {
            Auth::logout();

            throw ValidationException::withMessages([
                'identity' => 'Akun Anda ditolak oleh Pembina. Hubungi Pembina untuk informasi lebih lanjut.',
            ]);
        }

        // Regenerasi session ID untuk mencegah session fixation
        $request->session()->regenerate();

        // Lakukan autentikasi user
        Auth::login($user, $remember);

        // Pastikan session segera disimpan ke storage sebelum redirect,
        // agar cookie session tersedia pada request berikutnya.
        $request->session()->save();

        // Jika status pending dan email BELUM diverifikasi → pending approval.
        // Jika status pending tapi email SUDAH diverifikasi (lewat link aktivasi),
        // izinkan akses dashboard.
        if ($user->status === 'pending' && ! $user->hasVerifiedEmail()) {
            return redirect()->route('pending-approval');
        }

        // Jika email belum diverifikasi, arahkan ke halaman verifikasi
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('success', 'Email aktivasi Anda belum diverifikasi. Silakan periksa kotak masuk atau folder spam.');
        }

        // Arahkan ke dashboard berdasarkan role (intended URL jika ada)
        return redirect()->intended(
            $user->role === 'Alumni' ? route('alumni.dashboard') : route('dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    public function showRegisterForm(Request $request): Response
    {
        $latestAngkatan = Angkatan::query()
            ->where('is_active', true)
            ->where('is_current', true)
            ->first()
            ?? Angkatan::query()
                ->where('is_active', true)
                ->orderByDesc('nomor')
                ->first();

        return Inertia::render('Auth/Register', [
            'ambalan' => $this->ambalanData(),
            'latestAngkatan' => $latestAngkatan?->angkatan,
            'latestAngkatanNomor' => $latestAngkatan?->nomor,
            'latestAngkatanCurrent' => $latestAngkatan?->is_current,
            'gudepPrefix' => (string) config('app.gudep_prefix', '31082008'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $prefix = (string) config('app.gudep_prefix', '31082008');
        if (! preg_match('/^\d{1,22}$/', $prefix)) {
            abort(500, 'Konfigurasi GUDEP_PREFIX tidak valid.');
        }

        $latestAngkatan = Angkatan::query()
            ->where('is_active', true)
            ->where('is_current', true)
            ->first()
            ?? Angkatan::query()
                ->where('is_active', true)
                ->orderByDesc('nomor')
                ->first();

        if (! $latestAngkatan) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Belum ada angkatan aktif. Silakan hubungi Pembina.',
            ]);
        }

        $angkatanNomor = $latestAngkatan->nomor;

        $usedUrutan = Member::where('nta', 'like', "{$prefix}.{$angkatanNomor}.%")
            ->max('nomor_urut');
        $nextUrut = (int) $usedUrutan + 1;

        if ($nextUrut > 999) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Nomor urut untuk angkatan ini sudah mencapai 999.',
            ]);
        }

        $formattedUrut = str_pad((string) $nextUrut, 3, '0', STR_PAD_LEFT);
        $nta = sprintf('%s.%s.%s', $prefix, $angkatanNomor, $formattedUrut);

        $user = User::create([
            'username' => $data['email'],
            'name' => $data['nama_lengkap'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'pending',
        ]);

        $ambalan = Ambalan::first();
        if ($ambalan) {
            Member::create([
                'ambalan_id' => $ambalan->id,
                'user_id' => $user->id,
                'nta' => $nta,
                'angkatan' => $angkatanNomor,
                'nomor_urut' => (int) $formattedUrut,
                'nta_username' => $nta,
                'nama_lengkap' => $data['nama_lengkap'],
                'kelas' => '-',
                'tingkatan' => '-',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ]);
        }

        $mailSent = $user->sendActivationEmail();

        session()->flash(
            'success',
            $mailSent
                ? "Pendaftaran berhasil dengan email {$data['email']} dan NTA {$nta}. Email aktivasi telah dikirim, silakan cek kotak masuk atau folder spam Anda."
                : "Pendaftaran berhasil dengan email {$data['email']} dan NTA {$nta}, tetapi email aktivasi gagal dikirim. Silakan gunakan tombol \"Kirim Ulang Email Aktivasi\" di halaman masuk."
        );

        return redirect()->route('login');
    }

    // ------------------------------------------------------------------
    // Email verification
    // ------------------------------------------------------------------

    public function showVerificationNotice(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $request->user()?->email,
            'nta' => $request->user()?->member?->nta,
            'linkExpiryMinutes' => \App\Notifications\AccountActivationNotification::expiryMinutes(),
            'ambalan' => $this->ambalanData(),
        ]);
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        // Validasi signature URL (signed URL dari activation email).
        // Tidak memerlukan login — link sudah terlindungi oleh signature.
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->with(
                'error',
                'Link verifikasi tidak valid atau sudah kedaluwarsa. Silakan tekan "Kirim Ulang Verifikasi" untuk meminta tautan baru.'
            );
        }

        $targetId = (int) $request->route('id');
        $hash = (string) $request->route('hash');

        // Cari user berdasarkan ID dari URL (tanpa perlu auth)
        $user = User::find($targetId);

        if (! $user) {
            return redirect()->route('login')->with('error', 'Akun tidak ditemukan.');
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('error', 'Link verifikasi tidak cocok dengan akun ini.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Jika sudah login, arahkan ke dashboard. Jika belum, arahkan ke login.
        if ($request->user()) {
            return redirect()->intended(route('dashboard'))->with('success', 'Email berhasil diverifikasi. Selamat datang kembali.');
        }

        return redirect()->route('login')->with('success', 'Email berhasil diverifikasi. Silakan masuk untuk mengakses dashboard.');
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

    // ------------------------------------------------------------------
    // Resend activation (guest)
    // ------------------------------------------------------------------

    public function resendActivationLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user?->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Email Anda sudah terverifikasi. Silakan masuk.');
        }

        if ($user === null) {
            return back()->with('error', 'Email tidak terdaftar.');
        }

        $sent = $user->sendActivationEmail();

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Email aktivasi telah dikirim ke '.$user->email.'. Silakan cek kotak masuk dan folder spam Anda.'
                : 'Gagal mengirim email aktivasi. Silakan coba lagi beberapa saat lagi atau hubungi Pembina.'
        );
    }

    // ------------------------------------------------------------------
    // Pending approval
    // ------------------------------------------------------------------

    public function pendingApproval(Request $request): RedirectResponse|Response
    {
        if ($request->user() && $request->user()->status === 'approved') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/PendingApproval', [
            'ambalan' => $this->ambalanData(),
        ]);
    }

    // ------------------------------------------------------------------
    // Password reset (simple, no Fortify)
    // ------------------------------------------------------------------

    public function showForgotPasswordForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'ambalan' => $this->ambalanData(),
        ]);
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
            return back()->with('success', 'Tautan reset sandi telah dikirim ke '.$request->email.'.');
        }

        return back()->withErrors(['email' => 'Tidak dapat mengirim tautan reset sandi. Silakan coba lagi.']);
    }

    public function showResetPasswordForm(Request $request, $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->input('email'),
            'expiryMinutes' => 60,
            'ambalan' => $this->ambalanData(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Sandi berhasil diubah. Silakan masuk.');
        }

        return back()->withErrors(['email' => [__($status)]]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function ambalanData(): ?array
    {
        $ambalan = Ambalan::first();

        return $ambalan ? [
            'id' => $ambalan->id,
            'nama' => $ambalan->nama,
            'kode' => $ambalan->kode,
            'logo_path' => $ambalan->logo_path,
            'logo_url' => $ambalan->logo_url,
        ] : null;
    }
}
