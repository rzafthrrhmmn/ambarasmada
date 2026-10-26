<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisterController extends Controller
{
    private function currentAngkatan(): ?Angkatan
    {
        return Angkatan::query()
            ->whereRaw('"is_active" = true')
            ->whereRaw('"is_current" = true')
            ->first()
            ?? Angkatan::query()
                ->whereRaw('"is_active" = true')
                ->orderByDesc('nomor')
                ->first();
    }

    public function showRegistrationForm(Request $request): Response
    {
        $latestAngkatan = $this->currentAngkatan();

        return Inertia::render('Auth/Register', [
            'latestAngkatan' => $latestAngkatan?->angkatan,
            'latestAngkatanNomor' => $latestAngkatan?->nomor,
            'latestAngkatanCurrent' => $latestAngkatan?->is_current,
            'gudepPrefix' => (string) config('app.gudep_prefix', '31082008'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        try {
            return $this->handleRegistration($request);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('[register] GAGAL: '.get_class($e).': '.$e->getMessage()
                .' @ '.$e->getFile().':'.$e->getLine());

            return back()->with('error', 'Pendaftaran gagal diproses. Silakan coba lagi beberapa saat lagi.');
        }
    }

    private function handleRegistration(Request $request): RedirectResponse
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
}
