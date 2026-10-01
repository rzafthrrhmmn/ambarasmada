<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function pendingUsers(): Response
    {
        $pendingUsers = User::with('member')
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
            Member::create(['user_id' => $user->id, ...$memberData]);
        }

        return redirect()->route('members.pending')->with('success', "Akun {$user->email} disetujui dengan NTA {$nta}.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Pembina', 'Admin'], true), 403);

        $user->update(['status' => 'rejected']);

        return redirect()->route('members.pending')->with('success', "Akun {$user->email} ditolak.");
    }
}
