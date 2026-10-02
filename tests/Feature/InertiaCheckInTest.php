<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Meniru request Inertia persis seperti yang dikirim form.post() di Scan.vue.
 *
 * Test OfflineAttendanceQueueTest memakai postJson(), sedangkan aplikasi nyata
 * mengirim X-Inertia. Bentuk respons keduanya berbeda, jadi jalur Inertia
 * perlu diuji terpisah.
 */
class InertiaCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnggota(): array
    {
        $ambalan = Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => 'AMB',
            'status' => 'aktif',
        ]);

        $user = User::create([
            'username' => 'anggota.inertia@test.local',
            'name' => 'Anggota Inertia',
            'email' => 'anggota.inertia@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $member = Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $user->id,
            'nta' => '31082008.030.778',
            'angkatan' => '030',
            'nomor_urut' => 778,
            'nta_username' => '31082008.030.778',
            'nama_lengkap' => 'Anggota Inertia',
            'kelas' => '-',
            'tingkatan' => 'SMA',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);

        return [$user, $member];
    }

    private function makeSession(string $token, bool $withGeofence): AttendanceSession
    {
        $ambalan = Ambalan::first();

        $manager = User::create([
            'username' => 'pengurus.inertia@test.local',
            'name' => 'Pengurus Inertia',
            'email' => 'pengurus.inertia@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Pengurus',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        return AttendanceSession::create([
            'ambalan_id' => $ambalan->id,
            'nama' => 'Latihan Inertia',
            'tanggal' => now()->toDateString(),
            'qr_token' => $token,
            'qr_dynamic' => false,
            'created_by' => $manager->id,
            'latitude' => $withGeofence ? -5.1487 : null,
            'longitude' => $withGeofence ? 119.4324 : null,
            'radius' => $withGeofence ? 100 : null,
        ]);
    }

    /**
     * Header yang dikirim Inertia untuk form.post().
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia' => 'true',
            'Accept' => 'text/html, application/xhtml+xml',
        ];
    }

    /**
     * Ambil isi error bag seperti yang diterima form Inertia di klien.
     */
    private function sessionErrors(): array
    {
        $bag = session('errors');

        if (is_array($bag)) {
            $bag = $bag['default'] ?? $bag;
        }

        if (is_object($bag) && method_exists($bag, 'toArray')) {
            $bag = $bag->toArray();
        }

        if (! is_array($bag)) {
            return [];
        }

        return $bag['messages'] ?? $bag;
    }

    public function test_inertia_post_checkin_records_attendance(): void
    {
        [$user, $member] = $this->makeAnggota();
        $this->makeSession('QRSAMA', withGeofence: false);

        $response = $this->actingAs($user)->post('/attendance/check-in', [
            'qr_token' => 'QRSAMA',
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ], $this->inertiaHeaders());


        $this->assertSame(1, Attendance::count(), 'Presensi tidak tercatat lewat jalur Inertia.');
    }

    public function test_inertia_post_checkin_within_geofence_records_attendance(): void
    {
        [$user, $member] = $this->makeAnggota();
        $this->makeSession('QRGEO', withGeofence: true);

        $response = $this->actingAs($user)->post('/attendance/check-in', [
            'qr_token' => 'QRGEO',
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
            'latitude' => -5.14942,
            'longitude' => 119.4324,
        ], $this->inertiaHeaders());


        $this->assertSame(1, Attendance::count());
    }

    public function test_inertia_post_checkin_outside_radius_reports_error(): void
    {
        [$user, $member] = $this->makeAnggota();
        $session = $this->makeSession('QRJAUH', withGeofence: true);
        $session->update(['radius' => 50]);

        $response = $this->actingAs($user)->post('/attendance/check-in', [
            'qr_token' => 'QRJAUH',
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
            'latitude' => -5.4,
            'longitude' => 119.4324,
        ], $this->inertiaHeaders());

        $errors = $this->sessionErrors();


        $this->assertSame(0, Attendance::count());
        $this->assertArrayHasKey('location', $errors, 'Pesan geofence tidak terkirim ke klien.');
        $this->assertStringContainsString('terlalu jauh', mb_strtolower($errors['location'][0]));
        // Jarak aktual harus ikut tampil agar anggota tahu seberapa jauh.
        $this->assertMatchesRegularExpression('/Jarak Anda: \d+ m/', $errors['location'][0]);
        $this->assertStringContainsString('radius yang diizinkan', $errors['location'][0]);
    }

    public function test_inertia_post_checkin_without_geolocation_reports_error(): void
    {
        [$user, $member] = $this->makeAnggota();
        $this->makeSession('QRNOLOKASI', withGeofence: true);

        $response = $this->actingAs($user)->post('/attendance/check-in', [
            'qr_token' => 'QRNOLOKASI',
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ], $this->inertiaHeaders());

        $errors = $this->sessionErrors();


        $this->assertSame(0, Attendance::count());
        $this->assertArrayHasKey('location', $errors);
    }

    public function test_inertia_post_unknown_token(): void
    {
        [$user, $member] = $this->makeAnggota();

        $response = $this->actingAs($user)->post('/attendance/check-in', [
            'qr_token' => 'TOKENHANTU',
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ], $this->inertiaHeaders());


        // 404 tanpa header Inertia akan ditampilkan sebagai halaman error biasa,
        // bukan error formulir. Klien harus tetap bisa membaca JSON-nya.
        $this->assertSame(404, $response->getStatusCode());
    }
}
