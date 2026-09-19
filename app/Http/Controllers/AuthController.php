<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
            throw ValidationException::withMessages([
                'identity' => 'Kredensial yang dimasukkan salah.',
            ]);
        }

        if ($user->status === 'pending') {
            Auth::login($user, (bool) $credentials['remember']);
            $request->session()->regenerate();

            return redirect()->route('pending-approval');
        }

        if ($user->status === 'rejected') {
            Auth::logout();

            throw ValidationException::withMessages([
                'identity' => 'Akun Anda ditolak oleh Pembina. Hubungi Pembina untuk informasi lebih lanjut.',
            ]);
        }

        $request->session()->regenerate();
        Auth::login($user, (bool) $credentials['remember']);

        return Auth::user()->role === 'Alumni'
            ? redirect()->intended(route('alumni.dashboard'))
            : redirect()->intended(route('dashboard'));
    }

    public function showRegistrationForm(): Response
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

        $latestAngkatan = $this->currentAngkatan();

        if (! $latestAngkatan) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Belum ada angkatan aktif. Silakan hubungi Pembina.',
            ]);
        }

        $angkatanNomor = $latestAngkatan->nomor;
        $nextUrut = (int) User::where('username', 'like', "{$prefix}.{$angkatanNomor}.%")->count() + 1;

        if ($nextUrut > 999) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Nomor urut untuk angkatan ini sudah mencapai 999.',
            ]);
        }

        $formattedUrut = str_pad((string) $nextUrut, 3, '0', STR_PAD_LEFT);
        $username = sprintf('%s.%s.%s', $prefix, $angkatanNomor, $formattedUrut);

        $user = User::create([
            'username' => $username,
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
                'nta' => "{$prefix}.{$angkatanNomor}.{$formattedUrut}",
                'angkatan' => $angkatanNomor,
                'nomor_urut' => (int) $formattedUrut,
                'nta_username' => "{$prefix}.{$angkatanNomor}.{$formattedUrut}",
                'nama_lengkap' => $data['nama_lengkap'],
                'kelas' => '-',
                'tingkatan' => '-',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ]);
        }

        return redirect()->route('login')->with('success', "Akun dengan NTA {$username} berhasil dibuat. Menunggu persetujuan Pembina.");
    }

    public function pendingApproval(): Response
    {
        return Inertia::render('Auth/PendingApproval');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
