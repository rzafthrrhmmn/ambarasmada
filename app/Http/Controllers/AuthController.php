<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
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
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class AuthController extends Controller
{
    private function currentAngkatan(): ?Angkatan
    {
        return Angkatan::query()
            ->where('is_active', true)
            ->where('is_current', true)
            ->first()
            ?? Angkatan::query()
                ->where('is_active', true)
                ->orderByDesc('nomor')
                ->first();
    }

    public function showLoginForm(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if ($request->boolean('isRegistration')) {
            $latestAngkatan = $this->currentAngkatan();

            return Inertia::render('Auth/Login', [
                'isRegistration' => true,
                'latestAngkatan' => $latestAngkatan?->angkatan,
                'latestAngkatanNomor' => $latestAngkatan?->nomor,
                'latestAngkatanCurrent' => $latestAngkatan?->is_current,
                'gudepPrefix' => (string) config('app.gudep_prefix', '31082008'),
            ]);
        }

        return Inertia::render('Auth/Login', [
            'isRegistration' => false,
        ]);
    }

    public function login(Request $request): SymfonyResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $field = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $credentials['identity'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
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

        $activationMessage = 'Email aktivasi Anda belum diverifikasi. Silakan tekan "Kirim Ulang Verifikasi" untuk menerima tautan baru, lalu buka tautan tersebut.';
        $hasIntendedUrl = $request->session()->has('url.intended');

        // Prioritas 1: pengguna baru saja membuka link verifikasi dari email
        // tanpa login, sehingga middleware `auth` menyimpannya sebagai intended
        // URL. Teruskan ke sana agar aktivasi selesai tanpa langkah manual.
        if (! $user->hasVerifiedEmail() && $hasIntendedUrl) {
            return redirect()->intended(route('verification.notice'))->with('success', $activationMessage);
        }

        // Prioritas 2: akun pending menunggu persetujuan Pembina. Halaman ini
        // menyediakan tombol kirim ulang aktivasi bila email belum diverifikasi.
        if ($user->status === 'pending') {
            return redirect()->route('pending-approval');
        }

        // Prioritas 3: akun sudah disetujui tetapi email belum diverifikasi.
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('success', $activationMessage);
        }

        return redirect()->intended(
            Auth::user()->role === 'Alumni'
                ? route('alumni.dashboard')
                : route('dashboard')
        );
    }

    public function showRegistrationForm(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $latestAngkatan = $this->currentAngkatan();

        return Inertia::render('Auth/Login', [
            'isRegistration' => true,
            'latestAngkatan' => $latestAngkatan?->angkatan,
            'latestAngkatanNomor' => $latestAngkatan?->nomor,
            'latestAngkatanCurrent' => $latestAngkatan?->is_current,
            'gudepPrefix' => (string) config('app.gudep_prefix', '31082008'),
        ]);
    }

    public function register(Request $request): SymfonyResponse
    {
        try {
            return $this->handleRegistration($request);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            // error_log dipakai, bukan Log::, karena kanal log berbasis file
            // tidak dapat menulis pada filesystem Vercel yang hanya-baca.
            // Pesannya sengaja dibuat ringkas agar tidak terpotong oleh batas
            // ukuran log Vercel.
            error_log('[register] GAGAL: '.get_class($e).': '.$e->getMessage()
                .' @ '.$e->getFile().':'.$e->getLine());

            return back()->with('error', 'Pendaftaran gagal diproses. Silakan coba lagi beberapa saat lagi.');
        }
    }

    private function handleRegistration(Request $request): SymfonyResponse
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

        $latestAngkatan = $this->currentAngkatan();

        if (! $latestAngkatan) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Belum ada angkatan aktif. Silakan hubungi Pembina.',
            ]);
        }

        $angkatanNomor = $latestAngkatan->nomor;

        // Username kini berisi email, sehingga penomoran NTA tidak lagi
        // diturunkan dari users.username. Counter diambil dari tabel members,
        // tempat constraint unik (nta) dan (angkatan, nomor_urut) berada.
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

        // Username memakai email yang didaftarkan pengguna. Email sudah
        // dijamin unik oleh validasi di atas, jadi username juga unik.
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

    public function pendingApproval(): RedirectResponse|Response
    {
        if (Auth::check() && Auth::user()->status === 'approved') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/PendingApproval');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
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

        // Pemeriksaan HMAC yang sama dengan middleware `signed`, namun
        // ditangani dengan pesan yang bisa ditindaklanjuti pengguna.
        if (! $request->hasValidSignature()) {
            return redirect()->route('verification.notice')->with(
                'error',
                'Link verifikasi tidak valid atau sudah kedaluwarsa. Silakan tekan "Kirim Ulang Verifikasi" untuk meminta tautan baru.'
            );
        }

        $targetId = (int) $request->route('id');

        // Cegah pengguna memverifikasi email milik akun orang lain hanya dengan
        // membuka tautan di browser miliknya sendiri.
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

    /**
     * Kirim ulang tautan verifikasi bagi pengguna yang sudah login.
     */
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

    /**
     * Kirim ulang tautan aktivasi tanpa harus login, cukup dengan knowing email.
     *
     * Selalu mengembalikan pesan yang sama agar tidak membocorkan apakah sebuah
     * email terdaftar di sistem.
     */
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
