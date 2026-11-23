<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Member;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function pendingUsers(Request $request): Response
    {
        // Metode ini sebelumnya tidak punya pemeriksaan sendiri dan seluruhnya
        // bergantung pada middleware `pembina`. Menyandarkan diri pada satu
        // rute saja tidak cukup: begitu controller dipanggil dari konteks lain
        // atau middleware berubah, daftar pendaftar yang belum disetujui akan
        // terbuka tanpa cek sama sekali.
        Roles::guardApprover($request->user());

        // Bentuk array, bukan closure. User::member adalah HasOne, sehingga
        // closure diterima sebagai objek relasi yang tidak punya only() dan
        // akan melempar BadMethodCallException. Bentuk array adalah bentuk
        // yang benar untuk membatasi kolom pada eager load.
        // Pendaftar yang belum disetujui bukan anggota resmi, jadi tanggal
        // lahir dan nomor teleponnya tidak perlu ikut terbawa.
        $pendingUsers = User::with(['member' => ['id', 'user_id', 'nta', 'nta_username', 'nama_lengkap']])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Members/PendingUsers', [
            'pendingUsers' => $pendingUsers,
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Pembina', 'Admin'], true), 403);

        $user->update(['status' => 'approved']);

        $ambalan = Ambalan::first();

        // Username kini berisi email, bukan NTA. Identitas anggota diambil dari
        // record Member yang sudah dibuat saat pendaftaran.
        $member = Member::where('user_id', $user->id)->first();
        $nta = $member?->nta;

        if (! $nta) {
            $prefix = (string) config('app.gudep_prefix', '31082008');
            $angkatan = $member?->angkatan ?? '001';
            $nomorUrut = (int) ($member?->nomor_urut ?: (Member::where('angkatan', $angkatan)->max('nomor_urut') ?: 0) + 1);
            $nta = sprintf('%s.%s.%03d', $prefix, $angkatan, $nomorUrut);
        }

        $memberData = [
            'ambalan_id' => $ambalan?->id,
            'nta' => $nta,
            'angkatan' => $member?->angkatan ?? '001',
            'nomor_urut' => $member?->nomor_urut ?? 1,
            'nta_username' => $nta,
            'nama_lengkap' => $user->name,
            'kelas' => $member?->kelas ?? '-',
            'tingkatan' => $member?->tingkatan ?? 'Tamu',
            'status_aktif' => $member?->status_aktif ?? 'Aktif',
        ];

        if ($member) {
            $member->update($memberData);
        } else {
            $member = Member::create(['user_id' => $user->id, ...$memberData]);
        }

        // Generate QR code for KTA (Kartu Tanda Anggota) - 1 QR per account
        $member->generateQrCode();

        return redirect()->route('members.pending')->with('success', "Akun {$user->email} disetujui dengan NTA {$nta}.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Pembina', 'Admin'], true), 403);

        $user->update(['status' => 'rejected']);

        return redirect()->route('members.pending')->with('success', "Akun {$user->email} ditolak.");
    }

    public function publicForm(Request $request): Response
    {
        $ambalan = Ambalan::first();

        return Inertia::render('Registration/PublicForm', [
            'ambalan' => $ambalan ? [
                'id' => $ambalan->id,
                'nama' => $ambalan->nama,
                'kode' => $ambalan->kode,
                'logo_path' => $ambalan->logo_path,
                'logo_url' => $ambalan->logo_url ?: asset('images/Logo_Ambalan.png'),
            ] : null,
        ]);
    }

    public function publicStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'string', 'in:Laki-laki,Perempuan'],
            'kelas' => ['required', 'string', 'max:50'],
            'tingkatan' => ['required', 'string', 'in:Tamu,Bantara,Laksana'],
            'no_hp' => ['required', 'string', 'max:20'],
            'riwayat_pramuka' => ['nullable', 'string'],
            'alasan_gabung' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $data['nama_lengkap'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => bcrypt($data['password']),
            'role' => 'Anggota',
            'status' => 'pending',
        ]);

        Member::create([
            'user_id' => $user->id,
            'ambalan_id' => Ambalan::value('id') ?? 1,
            'nama_lengkap' => $data['nama_lengkap'],
            'tempat_lahir' => $data['tempat_lahir'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'kelas' => $data['kelas'],
            'tingkatan' => $data['tingkatan'],
            'no_hp' => $data['no_hp'],
            'status_aktif' => 'Aktif',
        ]);

        return redirect()->route('registration.public')->with('success', 'Pendaftaran berhasil dikirim. Tunggu konfirmasi dari Pembina.');
    }
}
