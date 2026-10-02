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
use Symfony\Component\HttpFoundation\JsonResponse;

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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'qr_dynamic' => ['sometimes', 'boolean'],
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
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'radius' => $data['radius'] ?? null,
            'qr_dynamic' => $request->boolean('qr_dynamic'),
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'qr_dynamic' => ['sometimes', 'boolean'],
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
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'radius' => $data['radius'] ?? null,
            'qr_dynamic' => $request->boolean('qr_dynamic'),
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

    public function checkIn(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->role === 'Anggota', 403);

        // Permintaan dari antrean offline mengirim Accept: application/json agar
        // kegagalan (sesi kedaluwarsa, di luar radius) terbaca sebagai status
        // HTTP yang bisa ditangani klien, bukan redirect yang selalu 200.
        $wantsJson = $request->expectsJson();

        $data = $request->validate([
            'qr_token' => ['required', 'string', 'max:100'],
            'member_id' => ['required', 'exists:members,id'],
            'keterangan' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $session = AttendanceSession::where('qr_token', strtoupper($data['qr_token']))->first();

        if (! $session) {
            if ($wantsJson) {
                return response()->json([
                    'message' => 'Sesi presensi tidak ditemukan. Kode QR mungkin sudah tidak berlaku.',
                ], 404);
            }

            abort(404);
        }

        // Sesi yang punya titik lokasi WAJIB memverifikasi jarak anggota.
        // Sebelumnya pemeriksaan ini dilewati diam-diam bila klien tidak
        // mengirim koordinat, sehingga geofence bisa dilewati total.
        $hasGeofence = $session->radius !== null && $session->latitude !== null && $session->longitude !== null;
        $clientSentCoords = isset($data['latitude'], $data['longitude']);

        if ($hasGeofence && ! $clientSentCoords) {
            $message = 'Lokasi perangkat tidak terkirim, sehingga jarak ke titik presensi tidak dapat '
                .'diverifikasi. Aktifkan izin lokasi untuk situs ini di pengaturan browser, lalu tekan '
                .'"Catat Kehadiran" lagi.';

            if ($wantsJson) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['location' => $message],
                ], 422);
            }

            return back()->withErrors(['location' => $message]);
        }

        // Check GPS radius if enabled
        if ($hasGeofence && $clientSentCoords) {
            $distance = $this->calculateDistance(
                $session->latitude,
                $session->longitude,
                $data['latitude'],
                $data['longitude']
            );

            if ($distance > $session->radius) {
                $message = "Anda terlalu jauh dari lokasi presensi. Jarak Anda: {$distance} m, "
                    ."radius yang diizinkan: {$session->radius} m. Dekati lokasi sesi lalu coba lagi.";

                if ($wantsJson) {
                    return response()->json([
                        'message' => $message,
                        'errors' => ['location' => $message],
                    ], 422);
                }

                return back()->withErrors(['location' => $message]);
            }
        }

        // updateOrCreate membuat endpoint ini idempoten: pengiriman ulang dari
        // antrean offline memperbarui baris yang sama, bukan membuat duplikat.
        Attendance::updateOrCreate(
            ['attendance_session_id' => $session->id, 'member_id' => $data['member_id']],
            [
                'keterangan' => $data['keterangan'],
                'checked_at' => now(),
                'catatan' => 'QR Code'.($clientSentCoords ? ' + GPS' : ''),
            ]
        );

        if ($wantsJson) {
            return response()->json(['message' => 'Presensi berhasil disimpan.']);
        }

        return redirect()->route('attendance.index')->with('success', 'Presensi berhasil disimpan.');
    }

    public function refreshQrToken(Request $request, AttendanceSession $attendanceSession): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        abort_unless($attendanceSession->qr_dynamic, 422, 'QR code dinamis tidak diaktifkan untuk sesi ini.');

        $attendanceSession->refreshQrToken();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance_session.qr_refreshed',
            'entity_type' => AttendanceSession::class,
            'entity_id' => $attendanceSession->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'qr_token' => $attendanceSession->qr_token,
            'refreshed_at' => $attendanceSession->qr_refreshed_at?->toISOString(),
        ]);
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

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }
}
