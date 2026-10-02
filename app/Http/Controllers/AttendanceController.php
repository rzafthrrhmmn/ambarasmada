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
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:all,ada,kosong'],
        ]);

        $filters['q'] = trim((string) ($filters['q'] ?? ''));
        $filters['status'] = $filters['status'] ?? 'all';

        $query = AttendanceSession::query()
            ->withCount('attendances')
            ->with(['ambalan', 'createdBy']);

        if ($filters['q'] !== '') {
            // Wildcard dibuang supaya hasil pencarian sama antara PostgreSQL dan
            // SQLite, yang berbeda aturan escaping untuk LIKE.
            $needle = '%'.str_replace(['%', '_'], '', $filters['q']).'%';

            $query->where(function ($inner) use ($needle) {
                $inner->where('nama', 'like', $needle)
                    ->orWhere('lokasi', 'like', $needle)
                    ->orWhere('qr_token', 'like', $needle);
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('tanggal', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('tanggal', '<=', $filters['to']);
        }

        if ($filters['status'] === 'ada') {
            $query->has('attendances');
        } elseif ($filters['status'] === 'kosong') {
            $query->doesntHave('attendances');
        }

        $sessions = $query->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $members = Member::where('status_aktif', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get();

        return Inertia::render('Attendance/Index', [
            'sessions' => $sessions,
            'members' => $members,
            'filters' => [
                'q' => $filters['q'],
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'status' => $filters['status'],
            ],
        ]);
    }

    public function show(AttendanceSession $attendanceSession): Response
    {
        $session = $attendanceSession->load(['attendances.member.user', 'ambalan']);

        // Dipakai form tambah presensi manual supaya petugas tidak perlu
        // mengingat NTA atau mencari anggota lewat halaman lain.
        $members = Member::where('status_aktif', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nta', 'angkatan']);

        return Inertia::render('Attendance/Show', [
            'session' => $session,
            'members' => $members,
        ]);
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

        // Ditentukan sebelum file diunggah supaya tidak ada file yatim bila
        // validasi gagal: kolom ambalan_id tidak boleh null.
        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;

        if (! $ambalanId) {
            throw ValidationException::withMessages([
                'ambalan_id' => 'Belum ada data Ambalan. Tambahkan Ambalan terlebih dahulu sebelum membuat sesi latihan.',
            ]);
        }

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
            'ambalan_id' => $ambalanId,
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

        // ValidationException (bukan abort 422) supaya Inertia mengirim galat
        // formulir ke klien. abort() hanya menghasilkan halaman 404/422 kosong
        // yang tidak menjelaskan apa pun ke anggota.
        $this->ensureSessionHasNoAttendance($attendanceSession);

        $this->deleteSessionMateri($attendanceSession);
        $name = $attendanceSession->nama;
        $attendanceSession->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance_session.deleted',
            'entity_type' => AttendanceSession::class,
            'entity_id' => $attendanceSession->id,
            'metadata' => ['nama' => $name],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('attendance.index')->with('success', "Sesi \"{$name}\" berhasil dihapus.");
    }

    /**
     * Hapus banyak sesi sekaligus.
     *
     * Sesi yang sudah punya presensi tidak dihapus; namanya dikembalikan ke
     * klien agar bisa ditampilkan sebagai daftar yang gagal, bukan hilang
     * tanpa penjelasan.
     */
    public function bulkDestroySessions(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $sessions = AttendanceSession::whereIn('id', $data['ids'])->get();

        $deleted = [];
        $blocked = [];

        foreach ($sessions as $session) {
            if ($session->attendances()->exists()) {
                $blocked[] = $session->nama;

                continue;
            }

            $this->deleteSessionMateri($session);
            $deleted[] = $session->nama;
            $session->delete();
        }

        // Id yang tidak ditemukan ikut dilaporkan agar jumlah "dipilih" dan
        // "terhapus" bisa dibandingkan tanpa tebakan.
        $missing = count($data['ids']) - $sessions->count();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance_session.bulk_deleted',
            'entity_type' => AttendanceSession::class,
            // entity_id tidak boleh null; aksi massal memakai id sesi pertama
            // yang terpengaruh, atau 0 bila tidak ada satupun.
            'entity_id' => $deleted[0] ?? 0,
            'metadata' => [
                'diminta' => count($data['ids']),
                'terhapus' => count($deleted),
                'ditolak' => $blocked,
                'tidak_ditemukan' => $missing,
            ],
            'ip_address' => $request->ip(),
        ]);

        $parts = [];

        if (count($deleted) > 0) {
            $parts[] = count($deleted).' sesi berhasil dihapus.';
        }

        if ($missing > 0) {
            $parts[] = $missing.' sesi sudah tidak ada.';
        }

        // Flash hanya boleh berisi satu pesan teks. FlashMessage membaca key
        // pertama dan akan menampilkan array apa adanya kalau ada lebih dari
        // satu key.
        if ($blocked) {
            $names = implode(', ', array_slice($blocked, 0, 3));
            $more = count($blocked) > 3 ? ' dan '.(count($blocked) - 3).' lainnya' : '';
            $parts[] = count($blocked).' sesi ditolak karena sudah punya presensi: '.$names.$more.'.';
        }

        if (! $parts) {
            $parts[] = 'Tidak ada sesi yang dihapus.';
        }

        return redirect()->route('attendance.index')
            ->with('success', implode(' ', $parts));
    }

    private function ensureSessionHasNoAttendance(AttendanceSession $session): void
    {
        if ($session->attendances()->exists()) {
            throw ValidationException::withMessages([
                'session' => "Sesi \"{$session->nama}\" sudah memiliki presensi sehingga tidak dapat dihapus. Hapus baris presensinya lebih dulu.",
            ]);
        }
    }

    private function deleteSessionMateri(AttendanceSession $session): void
    {
        if ($session->materi_path) {
            Storage::disk('public')->delete($session->materi_path);
        }
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

    public function storeAttendance(Request $request, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'keterangan' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        // updateOrCreate supaya anggota yang sama tidak punya dua baris presensi
        // untuk satu sesi ketika petugas/input ulang.
        $attendance = Attendance::updateOrCreate(
            [
                'attendance_session_id' => $attendanceSession->id,
                'member_id' => $data['member_id'],
            ],
            [
                'keterangan' => $data['keterangan'],
                'catatan' => $data['catatan'] ?? null,
                'checked_at' => now(),
            ]
        );

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance.created',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'metadata' => $data,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Presensi berhasil ditambahkan.');
    }

    public function destroyAttendance(Request $request, Attendance $attendance): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $sessionId = $attendance->attendance_session_id;
        $memberName = $attendance->member?->nama_lengkap;
        $attendance->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance.deleted',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'metadata' => ['member' => $memberName],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('attendance.show', $sessionId)->with('success', 'Presensi berhasil dihapus.');
    }

    /**
     * Aksi massal pada baris presensi satu sesi: ubah keterangan atau hapus.
     */
    public function bulkAttendance(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
            'action' => ['required', 'in:update,delete'],
            'keterangan' => ['required_if:action,update', 'in:Hadir,Izin,Sakit,Alpa'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $sessionId = (int) $request->input('attendance_session_id');
        $records = Attendance::whereIn('id', $data['ids'])->get();

        if ($data['action'] === 'delete') {
            $count = $records->count();
            $records->each->delete();

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'attendance.bulk_deleted',
                'entity_type' => Attendance::class,
                'entity_id' => $records->modelKeys()[0] ?? 0,
                'metadata' => ['jumlah' => $count, 'session_id' => $sessionId],
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('attendance.show', $sessionId)
                ->with('success', $count.' baris presensi berhasil dihapus.');
        }

        $attributes = ['keterangan' => $data['keterangan']];

        if (array_key_exists('catatan', $data)) {
            $attributes['catatan'] = $data['catatan'];
        }

        $records->each->update($attributes);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'attendance.bulk_updated',
            'entity_type' => Attendance::class,
            'entity_id' => $records->modelKeys()[0] ?? 0,
            'metadata' => $attributes + ['jumlah' => $records->count()],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('attendance.show', $sessionId)
            ->with('success', $records->count().' baris presensi berhasil diperbarui.');
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
