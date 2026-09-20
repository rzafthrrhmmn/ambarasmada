<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = AttendanceSession::query()
            ->withCount('attendances')
            ->with(['ambalan', 'createdBy'])
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();
        $members = Member::where('status_aktif', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get();

        return Inertia::render('Attendance/Index', [
            'sessions' => $sessions,
            'members' => $members,
        ]);
    }

    public function show(AttendanceSession $attendanceSession): Response
    {
        $session = $attendanceSession->load(['attendances.member.user', 'ambalan']);

        return Inertia::render('Attendance/Show', ['session' => $session]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'materi' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,mp4,webm,doc,docx'],
        ]);

        $materiPath = null;
        if ($request->hasFile('materi')) {
            $materiPath = $request->file('materi')->store('attendance-materi', 'public');
        }

        $session = AttendanceSession::create([
            'nama' => $data['nama'],
            'tanggal' => $data['tanggal'],
            'lokasi' => $data['lokasi'],
            'materi_path' => $materiPath,
            'materi_nama' => $materiPath ? $request->file('materi')->getClientOriginalName() : null,
            'materi_mime_type' => $materiPath ? $request->file('materi')->getMimeType() : null,
            'materi_size' => $materiPath ? $request->file('materi')->getSize() : null,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'qr_token' => Str::upper(Str::random(12)),
            'created_by' => $request->user()->id,
        ]);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance_session.created',
            'entity_type' => AttendanceSession::class,
            'entity_id' => $session->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('attendance.index')->with('success', 'Sesi latihan berhasil dibuat.');
    }

    public function update(Request $request, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'materi' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,mp4,webm,doc,docx'],
            'remove_materi' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('remove_materi') && $attendanceSession->materi_path) {
            Storage::disk('public')->delete($attendanceSession->materi_path);
            $attendanceSession->update([
                'materi_path' => null,
                'materi_nama' => null,
                'materi_mime_type' => null,
                'materi_size' => null,
            ]);
        }

        if ($request->hasFile('materi')) {
            if ($attendanceSession->materi_path) {
                Storage::disk('public')->delete($attendanceSession->materi_path);
            }

            $path = $request->file('materi')->store('attendance-materi', 'public');
            $attendanceSession->update([
                'materi_path' => $path,
                'materi_nama' => $request->file('materi')->getClientOriginalName(),
                'materi_mime_type' => $request->file('materi')->getMimeType(),
                'materi_size' => $request->file('materi')->getSize(),
            ]);
        }

        $attendanceSession->update([
            'nama' => $data['nama'],
            'tanggal' => $data['tanggal'],
            'lokasi' => $data['lokasi'],
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance_session.updated',
            'entity_type' => AttendanceSession::class,
            'entity_id' => $attendanceSession->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Sesi latihan berhasil diperbarui.');
    }

    public function destroy(Request $request, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        abort_if($attendanceSession->attendances()->exists(), 422, 'Sesi yang sudah memiliki presensi tidak dapat dihapus.');
        $attendanceSession->delete();

        return redirect()->route('attendance.index')->with('success', 'Sesi latihan berhasil dihapus.');
    }

    public function updateAttendance(Request $request, Attendance $attendance): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'keterangan' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $attendance->update($data);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance.updated',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'metadata' => $data,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('attendance.show', $attendance->attendance_session_id)->with('success', 'Presensi berhasil diperbarui.');
    }

    public function scanPage(string $qr_token): Response
    {
        abort_unless(in_array(request()->user()->role, ['Anggota']), 403);
        $session = AttendanceSession::where('qr_token', strtoupper($qr_token))->firstOrFail();

        return Inertia::render('Attendance/Scan', [
            'session' => $session,
            'member' => request()->user()->member,
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'Anggota', 403);
        $data = $request->validate([
            'qr_token' => ['required', 'string', 'max:100'],
            'member_id' => ['required', 'exists:members,id'],
            'keterangan' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
        ]);
        $session = AttendanceSession::where('qr_token', strtoupper($data['qr_token']))->firstOrFail();
        Attendance::updateOrCreate(
            ['attendance_session_id' => $session->id, 'member_id' => $data['member_id']],
            ['keterangan' => $data['keterangan'], 'checked_at' => now(), 'catatan' => 'QR Code'],
        );

        return redirect()->route('attendance.index')->with('success', 'Presensi berhasil disimpan.');
    }

    public function downloadMateri(AttendanceSession $attendanceSession): BinaryFileResponse
    {
        abort_unless($attendanceSession->materi_path && Storage::disk('public')->exists($attendanceSession->materi_path), 404);

        AuditLog::create([
            'actor_id' => request()->user()->id,
            'action' => 'attendance_session.materi_downloaded',
            'entity_type' => AttendanceSession::class,
            'entity_id' => $attendanceSession->id,
            'ip_address' => request()->ip(),
        ]);

        return Storage::disk('public')->download($attendanceSession->materi_path, $attendanceSession->materi_nama);
    }
}
