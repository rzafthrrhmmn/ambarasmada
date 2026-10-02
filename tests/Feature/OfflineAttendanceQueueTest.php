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

class OfflineAttendanceQueueTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnggota(): array
    {
        $ambalan = Ambalan::first() ?? Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => 'AMB',
            'status' => 'aktif',
        ]);

        $user = User::create([
            'username' => 'anggota.offline@test.local',
            'name' => 'Anggota Offline',
            'email' => 'anggota.offline@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $member = Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $user->id,
            'nta' => '31082008.030.777',
            'angkatan' => '030',
            'nomor_urut' => 777,
            'nta_username' => '31082008.030.777',
            'nama_lengkap' => 'Anggota Offline',
            'kelas' => '-',
            'tingkatan' => 'SMA',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);

        return [$user, $member];
    }

    private function makeSession(string $token = 'QRHARIAN', bool $withGeofence = false): AttendanceSession
    {
        $user = User::where('role', 'Pengurus')->first() ?? User::create([
            'username' => 'pengurus.offline@test.local',
            'name' => 'Pengurus Uji',
            'email' => 'pengurus.offline@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Pengurus',
            'is_active' => true,
            'status' => 'approved',
        ]);

        $session = AttendanceSession::create([
            'ambalan_id' => Ambalan::first()->id,
            'nama' => 'Latihan Rutin Offline',
            'qr_token' => $token,
            'qr_static' => true,
            'created_by' => $user->id,
            'status' => 'scheduled',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
        ]);

        if ($withGeofence) {
            $session->update([
                'latitude' => -5.1487,
                'longitude' => 119.4324,
                'radius' => 100,
            ]);
        }

        return $session->fresh();
    }

    public function test_queued_checkin_returns_json_success(): void
    {
        [$user, $member] = $this->makeAnggota();
        $session = $this->makeSession();

        $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'qrharian',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ])
            ->assertOk()
            ->assertJson(['message' => 'Presensi berhasil disimpan.']);

        $this->assertDatabaseHas('attendances', [
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ]);
    }

    public function test_replaying_queued_checkin_does_not_duplicate(): void
    {
        [$user, $member] = $this->makeAnggota();
        $this->makeSession();

        $payload = [
            'qr_token' => 'QRHARIAN',
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ];

        $this->actingAs($user)->postJson('/attendance/check-in', $payload)->assertOk();
        $this->actingAs($user)->postJson('/attendance/check-in', $payload)->assertOk();
        $this->actingAs($user)->postJson('/attendance/check-in', $payload)->assertOk();

        // Antrean offline bisa mengirim ulang berkali-kali; tetap satu baris.
        $this->assertSame(
            1,
            Attendance::where('member_id', $member->id)->count(),
            'Pengiriman ulang menghasilkan presensi ganda.'
        );
    }

    public function test_expired_qr_token_reports_404_for_queue(): void
    {
        [$user, $member] = $this->makeAnggota();

        $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'TOKENYANGKADALUARSA',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ])
            ->assertNotFound();

        $this->assertSame(0, Attendance::count());
    }

    public function test_out_of_radius_reports_422_for_queue(): void
    {
        [$user, $member] = $this->makeAnggota();

        $session = $this->makeSession('QRHARIAN', withGeofence: true);
        $session->update(['radius' => 50]);

        $response = $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
                'latitude' => -5.4000,
                'longitude' => 119.4324,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['location']]);
        $this->assertSame(0, Attendance::count());
    }

    public function test_checkin_inside_100m_radius_is_accepted(): void
    {
        [$user, $member] = $this->makeAnggota();

        // Sekitar 80 meter dari titik sesi (0.00072 derajat ~ 80 m).
        $this->makeSession('QRHARIAN', withGeofence: true);

        $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
                'latitude' => -5.14942,
                'longitude' => 119.4324,
            ])
            ->assertOk();

        $this->assertSame(1, Attendance::where('member_id', $member->id)->count());
    }

    public function test_geofence_session_rejects_checkin_without_client_coords(): void
    {
        [$user, $member] = $this->makeAnggota();

        $this->makeSession('QRHARIAN', withGeofence: true);

        // Inilah bug lama: klien tidak mengirim koordinat sehingga geofence
        // dilewati diam-diam dan presensi tetap diterima dari mana saja.
        $response = $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['location']]);
        $this->assertSame(0, Attendance::count(), 'Presensi diterima tanpa verifikasi lokasi.');
    }

    public function test_geofence_session_rejects_partial_coords(): void
    {
        [$user, $member] = $this->makeAnggota();

        $this->makeSession('QRHARIAN', withGeofence: true);

        $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
                'latitude' => -5.1487,
            ])
            ->assertStatus(422);

        $this->assertSame(0, Attendance::count());
    }

    public function test_session_without_geofence_still_accepts_missing_coords(): void
    {
        [$user, $member] = $this->makeAnggota();

        // Sesi lama tanpa titik lokasi harus tetap bisa dipakai.
        $this->makeSession('QRHARIAN', withGeofence: false);

        $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ])
            ->assertOk();

        $this->assertSame(1, Attendance::where('member_id', $member->id)->count());
    }

    private function makePengurus(string $suffix = 'geo'): User
    {
        // attendance_sessions.ambalan_id wajib diisi, jadi pastikan ada ambalan.
        if (! Ambalan::first()) {
            Ambalan::create([
                'nama' => 'Ambalan Uji',
                'kode' => 'AMB',
                'status' => 'aktif',
            ]);
        }

        return User::create([
            'username' => "pengurus.{$suffix}@test.local",
            'name' => 'Pengurus Geo',
            'email' => "pengurus.{$suffix}@test.local",
            'password' => Hash::make('rahasia12345'),
            'role' => 'Pengurus',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }

    public function test_manager_can_store_session_with_geofence(): void
    {
        $pengurus = $this->makePengurus();

        $response = $this->actingAs($pengurus)->post('/attendance', [
            'nama' => 'Latihan dengan Geofence',
            'tanggal' => now()->toDateString(),
            'lokasi' => 'Balai Ambalan',
            'latitude' => -5.14870000,
            'longitude' => 119.43240000,
            'radius' => 100,
        ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendance_sessions', [
            'nama' => 'Latihan dengan Geofence',
            'radius' => 100,
        ]);
        $this->assertNotNull(AttendanceSession::where('nama', 'Latihan dengan Geofence')->first()->latitude);
    }

    public function test_session_radius_is_bounded(): void
    {
        $pengurus = $this->makePengurus('geo2');

        $this->actingAs($pengurus)
            ->post('/attendance', [
                'nama' => 'Radius Terlalu Besar',
                'tanggal' => now()->toDateString(),
                'latitude' => -5.1487,
                'longitude' => 119.4324,
                'radius' => 5000,
            ])
            ->assertSessionHasErrors('radius');
    }

    public function test_invalid_keterangan_is_rejected(): void
    {
        [$user, $member] = $this->makeAnggota();
        $this->makeSession();

        $this->actingAs($user)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Ngawur',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('keterangan');
    }

    public function test_non_anggota_cannot_queue_checkin(): void
    {
        [$pengurus, $member] = $this->makeAnggota();
        $this->makeSession();

        $admin = User::where('role', 'Admin')->first() ?? User::create([
            'username' => 'admin.offline@test.local',
            'name' => 'Admin Uji',
            'email' => 'admin.offline@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Admin',
            'is_active' => true,
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->postJson('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_guest_cannot_queue_checkin(): void
    {
        $this->postJson('/attendance/check-in', [
            'qr_token' => 'QRHARIAN',
            'member_id' => 1,
            'keterangan' => 'Hadir',
        ])->assertUnauthorized();
    }

    public function test_browser_flow_still_redirects(): void
    {
        [$user, $member] = $this->makeAnggota();
        $this->makeSession();

        $this->actingAs($user)
            ->post('/attendance/check-in', [
                'qr_token' => 'QRHARIAN',
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ])
            ->assertRedirect(route('attendance.index'))
            ->assertSessionHas('success');
    }
}
