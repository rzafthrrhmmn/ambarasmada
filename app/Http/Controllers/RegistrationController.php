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
        $pendingUsers = User::where('status', 'pending')->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Members/PendingUsers', [
            'pendingUsers' => $pendingUsers,
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);

        $user->update(['status' => 'approved']);

        $ambalan = Ambalan::first();
        $angkatanParts = explode('.', $user->username);
        $angkatanNomor = $angkatanParts[1] ?? '001';
        $nomorUrut = (int) ($angkatanParts[2] ?? 1);

        $memberData = [
            'ambalan_id' => $ambalan?->id ?? 1,
            'nta' => $user->username,
            'angkatan' => $angkatanNomor,
            'nomor_urut' => $nomorUrut,
            'nta_username' => $user->username,
            'nama_lengkap' => $user->name,
            'kelas' => '-',
            'tingkatan' => 'Tamu',
            'status_aktif' => 'Aktif',
        ];

        $member = Member::where('user_id', $user->id)->first();
        if ($member) {
            $member->update($memberData);
        } else {
            Member::create(['user_id' => $user->id, ...$memberData]);
        }

        return redirect()->route('members.pending')->with('success', "Akun {$user->username} disetujui.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);

        $user->update(['status' => 'rejected']);

        return redirect()->route('members.pending')->with('success', "Akun {$user->username} ditolak.");
    }
}
