<?php

namespace App\Http\Controllers;

use App\Models\AlumniProfile;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlumniController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $member = Member::where('user_id', $request->user()->id)
            ->where('status_aktif', 'Alumni')
            ->firstOrFail();
        $profile = $member->alumniProfile()->firstOrCreate([]);
        $donations = Donation::where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->get();
        $announcements = Announcement::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();
        $directory = Member::query()
            ->where('status_aktif', 'Alumni')
            ->where('id', '!=', $member->id)
            ->with(['alumniProfile', 'user'])
            ->orderBy('nama_lengkap')
            ->get();

        return Inertia::render('Alumni/Dashboard', [
            'member' => $member,
            'profile' => $profile,
            'donations' => $donations,
            'announcements' => $announcements,
            'directory' => $directory,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $member = Member::where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate([
            'status_saat_ini' => ['required', 'in:Kuliah,Bekerja,Wirausaha,Lainnya'],
            'instansi_kampus' => ['nullable', 'string', 'max:255'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'domisili' => ['nullable', 'string', 'max:255'],
            'media_sosial' => ['nullable', 'string', 'max:255'],
            'show_contact' => ['nullable', 'boolean'],
        ]);
        $profile = $member->alumniProfile()->firstOrCreate([]);
        $profile->update($data);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'alumni.profile.updated',
            'entity_type' => AlumniProfile::class,
            'entity_id' => $profile->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('alumni.dashboard')->with('success', 'Profil alumni berhasil diperbarui.');
    }

    public function storeDonation(Request $request): RedirectResponse
    {
        $member = Member::where('user_id', $request->user()->id)
            ->where('status_aktif', 'Alumni')
            ->firstOrFail();
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'keterangan_alokasi' => ['nullable', 'string', 'max:500'],
            'bukti_transfer' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ]);
        $path = $request->file('bukti_transfer')->store('donations', 'public');
        Donation::create([
            'member_id' => $member->id,
            'nominal' => $data['nominal'],
            'bukti_transfer' => $path,
            'keterangan_alokasi' => $data['keterangan_alokasi'] ?: null,
            'status_verifikasi' => 'Pending',
        ]);

        return redirect()->route('alumni.dashboard')->with('success', 'Komitmen donasi berhasil diajukan.');
    }

    public function cancelDonation(Request $request, Donation $donation): RedirectResponse
    {
        $member = Member::where('user_id', $request->user()->id)->firstOrFail();
        abort_if($donation->member_id !== $member->id, 403);
        abort_if($donation->status_verifikasi !== 'Pending', 422);
        $donation->update(['status_verifikasi' => 'Rejected', 'cancelled_at' => now()]);

        return redirect()->route('alumni.dashboard')->with('success', 'Komitmen donasi dibatalkan.');
    }

    public function verifyDonation(Request $request, Donation $donation): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'status_verifikasi' => ['required', 'in:Approved,Rejected'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $donation->update([
            'status_verifikasi' => $data['status_verifikasi'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'catatan' => $data['catatan'] ?: null,
        ]);

        return redirect()->route('alumni.dashboard')->with('success', 'Donasi berhasil diverifikasi.');
    }
}
