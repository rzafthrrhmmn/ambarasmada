<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pembatasan tampilan dan data modul kehadiran per peran.
 *
 * Halaman /attendance terbuka bagi semua pengguna terverifikasi, sehingga
 *_Inertia::render() yang sama harus served ke dua audiens yang berbeda:
 * pengelola (Admin, Pembina, Pengurus) dan anggota.
 *
 * Yang diuji di sini bukan hanya komponen mana yang dirender, melainkan juga
 * data apa yang boleh sampai ke browser anggota: direktori anggota, presensi
 * anggota lain, dan koordinat titik geofence tidak boleh ikut terkirim.
 */
class AttendanceRoleViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $suffix = strtolower($role).'.'.uniqid().'@test.local';

        return User::create([
            'username' => $suffix,
            'name' => $role.' Uji',
            'email' => $suffix,
            'password' => Hash::make('rahasia12345'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }

    private function makeAmbalan(): Ambalan
    {
        return Ambalan::first() ?: Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => 'AMB',
            'status' => 'aktif',
        ]);
    }

    private function makeMember(?User $user = null): Member
    {
        return Member::create([
            'ambalan_id' => $this->makeAmbalan()->id,
            'user_id' => ($user ?: $this->makeUser('Anggota'))->id,
            'nta' => '31082008.030.'.random_int(100, 999),
            'angkatan' => '030',
            'nomor_urut' => random_int(100, 999),
            'nta_username' => 'x'.random_int(1000, 9999),
            'nama_lengkap' => 'Anggota Uji',
            'kelas' => 'XII IPA 1',
            'tingkatan' => 'Laksana',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);
    }

    private function makeSession(array $overrides = []): AttendanceSession
    {
        return AttendanceSession::create(array_merge([
            'ambalan_id' => $this->makeAmbalan()->id,
            'nama' => 'Latihan Rutin',
            'tanggal' => now()->toDateString(),
            'lokasi' => 'Lapangan Sekolah',
            'latitude' => -5.13840000,
            'longitude' => 119.40890000,
            'radius' => 150,
            'qr_token' => strtoupper(uniqid()),
            'qr_dynamic' => false,
            'created_by' => $this->makeUser('Admin')->id,
        ], $overrides));
    }

    public function test_pengelola_menerima_halaman_kelola_dengan_direktori_anggota(): void
    {
        $pengelola = $this->makeUser('Pengurus');
        $this->makeMember();
        $this->makeSession();

        $this->actingAs($pengelola)
            ->get('/attendance')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/Index')
                ->has('sessions')
                ->has('members', 1)
                ->has('filters')
                ->missing('summary')
            );
    }

    public function test_anggota_menerima_halaman_kehadiran_pribadi_tanpa_direktori_anggota(): void
    {
        $member = $this->makeMember();
        $this->makeMember(); // anggota lain yang tidak boleh terlihat
        $this->makeSession();

        $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/MemberIndex')
                ->has('sessions')
                ->has('member.nama_lengkap')
                ->has('summary')
                ->missing('members')
                ->missing('filters')
            );
    }

    public function test_halaman_anggota_tidak_membocorkan_koordinat_geofence(): void
    {
        $member = $this->makeMember();
        $session = $this->makeSession();

        $response = $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk();

        $payload = $response->viewData('page')['props'];

        $serialized = json_encode($payload['sessions']);

        $this->assertStringNotContainsString('latitude', $serialized);
        $this->assertStringNotContainsString('longitude', $serialized);
        $this->assertStringNotContainsString('createdBy', $serialized);

        // Radius tetap boleh tampil karena sudah diketahui anggota lewat halaman
        // pemindai, dan dibutuhkan untuk menjelaskan Radius presensi.
        $this->assertSame(150, $payload['sessions']['data'][0]['radius']);
        $this->assertSame($session->id, $payload['sessions']['data'][0]['id']);
    }

    public function test_halaman_anggota_hanya_menampilkan_status_kehadiran_sendiri(): void
    {
        $member = $this->makeMember();
        $other = $this->makeMember();
        $session = $this->makeSession();

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
            'checked_at' => now(),
        ]);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $other->id,
            'keterangan' => 'Alpa',
            'catatan' => 'Keterangan Wajib Milik Anggota Lain',
        ]);

        $payload = $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk()
            ->viewData('page')['props'];

        $sessionPayload = $payload['sessions']['data'][0];

        $this->assertSame('Hadir', $sessionPayload['my_attendance']['keterangan']);
        $this->assertNotNull($sessionPayload['my_attendance']['checked_at']);

        $serialized = json_encode($payload);

        $this->assertStringNotContainsString('Keterangan Wajib Milik Anggota Lain', $serialized);
        $this->assertStringNotContainsString($other->nta, $serialized);
    }

    public function test_ringkasan_kehadiran_hanya_menghitung_catatan_sendiri(): void
    {
        $member = $this->makeMember();
        $other = $this->makeMember();
        $session = $this->makeSession();

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
            'checked_at' => now(),
        ]);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $other->id,
            'keterangan' => 'Hadir',
            'checked_at' => now(),
        ]);

        $payload = $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(1, $payload['summary']['total']);
        $this->assertSame(1, $payload['summary']['hadir']);
        $this->assertSame(100, $payload['summary']['percentage']);
    }

    public function test_anggota_tidak_menerima_presensi_anggota_lain_di_halaman_detail(): void
    {
        $member = $this->makeMember();
        $other = $this->makeMember();
        $session = $this->makeSession();

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $other->id,
            'keterangan' => 'Hadir',
            'catatan' => 'Baris Presensi Anggota Lain',
            'checked_at' => now(),
        ]);

        $payload = $this->actingAs($member->user)
            ->get("/attendance/{$session->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/MemberShow')
                ->has('session')
                ->where('myAttendance', null)
                ->missing('members')
            )
            ->viewData('page')['props'];

        $serialized = json_encode($payload);

        $this->assertStringNotContainsString('Baris Presensi Anggota Lain', $serialized);
        $this->assertStringNotContainsString($other->nta, $serialized);
        $this->assertStringNotContainsString('latitude', $serialized);
        $this->assertStringNotContainsString('longitude', $serialized);
    }

    public function test_pengelola_menerima_halaman_detail_lengkap_dengan_seluruh_presensi(): void
    {
        $pengelola = $this->makeUser('Pembina');
        $member = $this->makeMember();
        $session = $this->makeSession();

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
            'checked_at' => now(),
        ]);

        $this->actingAs($pengelola)
            ->get("/attendance/{$session->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/Show')
                ->has('session.attendances', 1)
                ->has('members', 1)
            );
    }

    public function test_anggota_tetap_boleh_mengunduh_materi(): void
    {
        Storage::fake('public');

        $member = $this->makeMember();
        $ambalan = $this->makeAmbalan();
        $path = $ambalan->nama.'.pdf';
        Storage::disk('public')->put('attendance-materi/'.$path, 'isi materi');

        $session = $this->makeSession([
            'materi_path' => 'attendance-materi/'.$path,
            'materi_nama' => $path,
            'materi_mime_type' => 'application/pdf',
            'materi_size' => 9,
        ]);

        $this->actingAs($member->user)
            ->get("/attendance/{$session->id}/materi")
            ->assertOk();
    }

    public function test_presensi_hanya_bisa_dikirim_oleh_anggota(): void
    {
        $pengelola = $this->makeUser('Pengurus');
        $member = $this->makeMember();
        $session = $this->makeSession();

        $payload = [
            'qr_token' => $session->qr_token,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ];

        $this->actingAs($pengelola)
            ->post('/attendance/check-in', $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_anggota_tidak_bisa_menulis_presensi_untuk_anggota_lain(): void
    {
        $member = $this->makeMember();
        $victim = $this->makeMember();
        // Tanpa geofence supaya pengujian berfokus pada kepemilikan baris
        // presensi, bukan pada perhitungan jarak.
        $session = $this->makeSession(['latitude' => null, 'longitude' => null, 'radius' => null]);

        // formerly member_id hanya dicek exists:members,id, jadi nilai dari
        // klien dipercaya penuh dan presensi orang lain bisa ditimpa.
        $this->actingAs($member->user)
            ->post('/attendance/check-in', [
                'qr_token' => $session->qr_token,
                'member_id' => $victim->id,
                'keterangan' => 'Hadir',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendances', [
            'attendance_session_id' => $session->id,
            'member_id' => $victim->id,
        ]);

        // Yang tercatat harus miliknya sendiri, bukan id yang dikirim.
        $this->assertDatabaseHas('attendances', [
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
        ]);
    }

    public function test_anggota_tidak_bisa_menimpa_presensi_yang_sudah_ada_miliknya(): void
    {
        $member = $this->makeMember();
        $other = $this->makeMember();
        $session = $this->makeSession(['latitude' => null, 'longitude' => null, 'radius' => null]);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Alpa',
            'catatan' => 'Alasan Lama',
            'checked_at' => now(),
        ]);

        $this->actingAs($member->user)
            ->post('/attendance/check-in', [
                'qr_token' => $session->qr_token,
                'member_id' => $member->id,
                'keterangan' => 'Hadir',
            ])
            ->assertRedirect();

        $record = Attendance::where('member_id', $member->id)->firstOrFail();

        $this->assertSame('Hadir', $record->keterangan);
        $this->assertSame(1, Attendance::where('member_id', $member->id)->count());

        $this->assertDatabaseMissing('attendances', [
            'attendance_session_id' => $session->id,
            'member_id' => $other->id,
        ]);
    }

    public function test_token_qr_hanya_dikirim_untuk_sesi_hari_ini_yang_belum_tercatat(): void
    {
        $member = $this->makeMember();

        $today = $this->makeSession(['nama' => 'Sesi Hari Ini']);
        $past = $this->makeSession(['nama' => 'Sesi Lampau', 'tanggal' => now()->subDay()->toDateString()]);
        $future = $this->makeSession(['nama' => 'Sesi Mendatang', 'tanggal' => now()->addDay()->toDateString()]);
        $dynamic = $this->makeSession([
            'nama' => 'Sesi QR Dinamis',
            'tanggal' => now()->toDateString(),
            'qr_dynamic' => true,
        ]);

        $payload = $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk()
            ->viewData('page')['props'];

        $byName = collect($payload['sessions']['data'])->keyBy('nama');

        $this->assertTrue($byName['Sesi Hari Ini']['can_scan']);
        $this->assertSame($today->qr_token, $byName['Sesi Hari Ini']['qr_token']);

        foreach (['Sesi Lampau' => $past, 'Sesi Mendatang' => $future, 'Sesi QR Dinamis' => $dynamic] as $nama => $session) {
            $this->assertFalse($byName[$nama]['can_scan'], "{$nama} tidak boleh bisa dipindai");
            $this->assertArrayNotHasKey('qr_token', $byName[$nama], "{$nama} tidak boleh mengirim token");
            $this->assertStringNotContainsString($session->qr_token, json_encode($payload['sessions']));
        }
    }

    public function test_token_qr_ditarik_setelah_kehadiran_tercatat(): void
    {
        $member = $this->makeMember();
        $session = $this->makeSession(['tanggal' => now()->toDateString()]);

        $payload = $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertTrue($payload['sessions']['data'][0]['can_scan']);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => $member->id,
            'keterangan' => 'Hadir',
            'checked_at' => now(),
        ]);

        $payload = $this->actingAs($member->user)
            ->get('/attendance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertFalse($payload['sessions']['data'][0]['can_scan']);
        $this->assertArrayNotHasKey('qr_token', $payload['sessions']['data'][0]);
    }

    public function test_anggota_tanpa_profil_member_mendapat_empty_state(): void
    {
        // Anggota tanpa baris members. Alumni tidak mungkin sampai ke sini
        // karena middleware EnsureNotAlumni mengalihkannya lebih dulu.
        $tanpaProfil = $this->makeUser('Anggota');
        $this->makeSession();

        $this->actingAs($tanpaProfil)
            ->get('/attendance')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/MemberIndex')
                ->where('linked', false)
                ->where('member', null)
            );
    }

    public function test_detail_sesi_anggota_menyembunyikan_token_qr_yang_tidak_boleh_dipakai(): void
    {
        $member = $this->makeMember();
        $past = $this->makeSession(['tanggal' => now()->subWeek()->toDateString()]);
        $today = $this->makeSession(['tanggal' => now()->toDateString(), 'nama' => 'Sesi Hari Ini']);

        $serialized = function (int $sessionId) use ($member): string {
            return json_encode(
                $this->actingAs($member->user)
                    ->get("/attendance/{$sessionId}")
                    ->assertOk()
                    ->viewData('page')['props']
            );
        };

        $this->assertStringNotContainsString($past->qr_token, $serialized($past->id));
        $this->assertStringContainsString($today->qr_token, $serialized($today->id));
    }
}
