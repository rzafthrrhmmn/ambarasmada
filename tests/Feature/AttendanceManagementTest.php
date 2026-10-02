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
 * Pengelolaan sesi latihan dan baris presensi: ubah, hapus, hapus massal,
 * dan tambah presensi manual.
 */
class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'Pengurus'): User
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
        return Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => 'AMB',
            'status' => 'aktif',
        ]);
    }

    private function makeSession(array $overrides = []): AttendanceSession
    {
        $ambalan = Ambalan::first() ?? $this->makeAmbalan();

        return AttendanceSession::create(array_merge([
            'ambalan_id' => $ambalan->id,
            'nama' => 'Latihan Uji',
            'tanggal' => now()->toDateString(),
            'qr_token' => strtoupper(uniqid()),
            'qr_dynamic' => false,
            'created_by' => $this->makeUser()->id,
        ], $overrides));
    }

    private function makeMember(): Member
    {
        $ambalan = Ambalan::first();

        return Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $this->makeUser('Anggota')->id,
            'nta' => '31082008.030.'.random_int(100, 999),
            'angkatan' => '030',
            'nomor_urut' => random_int(100, 999),
            'nta_username' => 'x'.random_int(1000, 9999),
            'nama_lengkap' => 'Anggota Uji',
            'kelas' => '-',
            'tingkatan' => 'SMA',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);
    }

    private function makeAttendance(AttendanceSession $session, ?Member $member = null): Attendance
    {
        return Attendance::create([
            'attendance_session_id' => $session->id,
            'member_id' => ($member ?? $this->makeMember())->id,
            'keterangan' => 'Hadir',
            'checked_at' => now(),
        ]);
    }

    public function test_admin_can_create_and_update_session(): void
    {
        $admin = $this->makeUser('Admin');
        $this->makeAmbalan();

        $this->actingAs($admin)->post('/attendance', [
            'nama' => 'Latihan Baru',
            'tanggal' => now()->toDateString(),
            'lokasi' => 'Lapangan',
        ])->assertRedirect(route('attendance.index'));

        $session = AttendanceSession::where('nama', 'Latihan Baru')->firstOrFail();

        $this->actingAs($admin)->patch("/attendance/{$session->id}", [
            'nama' => 'Latihan Diubah',
            'tanggal' => now()->toDateString(),
            'lokasi' => 'Aula',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Latihan Diubah', $session->fresh()->nama);
    }

    public function test_store_fails_with_clear_error_when_no_ambalan_exists(): void
    {
        $admin = $this->makeUser('Admin');

        $this->assertSame(0, Ambalan::count());

        $this->actingAs($admin)->post('/attendance', [
            'nama' => 'Tanpa Ambalan',
            'tanggal' => now()->toDateString(),
        ])->assertSessionHasErrors('ambalan_id');

        $this->assertSame(0, AttendanceSession::count());
    }

    public function test_member_cannot_create_session(): void
    {
        $this->actingAs($this->makeUser('Anggota'))
            ->post('/attendance', ['nama' => 'Ditolak', 'tanggal' => now()->toDateString()])
            ->assertForbidden();
    }

    public function test_session_with_attendance_cannot_be_deleted(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession(['nama' => 'Sesi Berisiko']);
        $this->makeAttendance($session);

        $this->actingAs($pengurus)
            ->delete("/attendance/{$session->id}")
            ->assertSessionHasErrors('session');

        // Pesan harus menyebut nama sesi, bukan "Sesi latihan gagal".
        $this->assertStringContainsString(
            'Sesi Berisiko',
            session('errors')->first('session')
        );

        $this->assertNotNull($session->fresh());
    }

    public function test_empty_session_can_be_deleted(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();

        $this->actingAs($pengurus)
            ->delete("/attendance/{$session->id}")
            ->assertRedirect(route('attendance.index'));

        $this->assertSoftDeleted('attendance_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_session.deleted']);
    }

    public function test_bulk_delete_skips_sessions_with_attendance_and_reports_them(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $kosong = $this->makeSession(['nama' => 'Sesi Kosong']);
        $berisi = $this->makeSession(['nama' => 'Sesi Ada Presensi']);
        $this->makeAttendance($berisi);

        $response = $this->actingAs($pengurus)->post('/attendance/bulk-destroy', [
            'ids' => [$kosong->id, $berisi->id],
        ]);

        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHasNoErrors();

        $flash = session('success');

        $this->assertStringContainsString('1 sesi berhasil dihapus', $flash);
        $this->assertStringContainsString('Sesi Ada Presensi', $flash);

        $this->assertSoftDeleted('attendance_sessions', ['id' => $kosong->id]);
        $this->assertNotNull($berisi->fresh());
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_session.bulk_deleted']);
    }

    public function test_bulk_delete_requires_at_least_one_id(): void
    {
        $this->actingAs($this->makeUser('Pengurus'))
            ->post('/attendance/bulk-destroy', ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_anggota_cannot_bulk_delete_sessions(): void
    {
        $session = $this->makeSession();

        $this->actingAs($this->makeUser('Anggota'))
            ->post('/attendance/bulk-destroy', ['ids' => [$session->id]])
            ->assertForbidden();

        $this->assertNotNull($session->fresh());
    }

    public function test_search_filters_sessions_by_name(): void
    {
        $this->actingAs($this->makeUser('Pengurus'));
        $this->makeSession(['nama' => 'Latihan Pagi']);
        $this->makeSession(['nama' => 'Latihan Sore']);

        $this->get('/attendance?q=Pagi')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/Index')
                ->where('sessions.data', fn ($items) => $this->namesMatch($items, 'Latihan Pagi'))
            );
    }

    public function test_status_filter_separates_sessions_with_and_without_attendance(): void
    {
        $this->actingAs($this->makeUser('Pengurus'));
        $ada = $this->makeSession(['nama' => 'Punya Presensi']);
        $this->makeSession(['nama' => 'Tanpa Presensi']);
        $this->makeAttendance($ada);

        $this->get('/attendance?status=ada')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sessions.data', fn ($items) => $this->namesMatch($items, 'Punya Presensi'))
            );

        $this->get('/attendance?status=kosong')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sessions.data', fn ($items) => $this->namesMatch($items, 'Tanpa Presensi'))
            );
    }

    /** Sesi yang diminta harus ada, dan sesi lain yang sengaja dibuat harus tidak bocor. */
    private function namesMatch($items, string $harusAda): bool
    {
        $names = collect($items)->pluck('nama');

        return $names->contains($harusAda) && $names->count() === 1;
    }

    public function test_index_rejects_invalid_filter_values(): void
    {
        $this->actingAs($this->makeUser('Pengurus'))
            ->get('/attendance?status=bogus')
            ->assertSessionHasErrors('status');
    }

    public function test_manual_attendance_can_be_added_and_is_idempotent(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();
        $member = $this->makeMember();

        foreach (['Hadir', 'Izin'] as $keterangan) {
            $this->actingAs($pengurus)->post("/attendance/{$session->id}/records", [
                'member_id' => $member->id,
                'keterangan' => $keterangan,
            ])->assertSessionHasNoErrors();
        }

        // Dua kali input untuk anggota sama harus tetap satu baris.
        $this->assertSame(1, Attendance::where('attendance_session_id', $session->id)->count());

        $this->assertSame(
            'Izin',
            Attendance::where('attendance_session_id', $session->id)->firstOrFail()->keterangan
        );
    }

    public function test_manual_attendance_rejects_invalid_status(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();

        $this->actingAs($pengurus)->post("/attendance/{$session->id}/records", [
            'member_id' => $this->makeMember()->id,
            'keterangan' => 'Tidak Sah',
        ])->assertSessionHasErrors('keterangan');

        $this->assertSame(0, Attendance::count());
    }

    public function test_single_attendance_can_be_deleted(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();
        $attendance = $this->makeAttendance($session);

        $this->actingAs($pengurus)
            ->delete("/attendance-records/{$attendance->id}")
            ->assertRedirect(route('attendance.show', $session->id));

        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.deleted']);
    }

    public function test_bulk_update_changes_keterangan_for_selected_rows(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();
        $a = $this->makeAttendance($session);
        $b = $this->makeAttendance($session);
        $luar = $this->makeAttendance($this->makeSession());

        $this->actingAs($pengurus)->post('/attendance-records/bulk', [
            'ids' => [$a->id, $b->id],
            'action' => 'update',
            'attendance_session_id' => $session->id,
            'keterangan' => 'Alpa',
        ])->assertRedirect(route('attendance.show', $session->id));

        $this->assertSame('Alpa', $a->fresh()->keterangan);
        $this->assertSame('Alpa', $b->fresh()->keterangan);
        $this->assertSame('Hadir', $luar->fresh()->keterangan);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.bulk_updated']);
    }

    public function test_bulk_delete_removes_selected_rows_only(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();
        $a = $this->makeAttendance($session);
        $b = $this->makeAttendance($session);
        $luar = $this->makeAttendance($this->makeSession());

        $this->actingAs($pengurus)->post('/attendance-records/bulk', [
            'ids' => [$a->id, $b->id],
            'action' => 'delete',
            'attendance_session_id' => $session->id,
        ])->assertRedirect(route('attendance.show', $session->id));

        $this->assertDatabaseMissing('attendances', ['id' => $a->id]);
        $this->assertDatabaseMissing('attendances', ['id' => $b->id]);
        $this->assertDatabaseHas('attendances', ['id' => $luar->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.bulk_deleted']);
    }

    public function test_bulk_update_requires_keterangan(): void
    {
        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession();
        $attendance = $this->makeAttendance($session);

        $this->actingAs($pengurus)->post('/attendance-records/bulk', [
            'ids' => [$attendance->id],
            'action' => 'update',
            'attendance_session_id' => $session->id,
        ])->assertSessionHasErrors('keterangan');

        $this->assertSame('Hadir', $attendance->fresh()->keterangan);
    }

    public function test_anggota_cannot_delete_attendance(): void
    {
        $session = $this->makeSession();
        $attendance = $this->makeAttendance($session);

        $this->actingAs($this->makeUser('Anggota'))
            ->delete("/attendance-records/{$attendance->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_deleting_session_removes_its_material_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('attendance-materi/contoh.pdf', 'isi');

        $pengurus = $this->makeUser('Pengurus');
        $session = $this->makeSession(['materi_path' => 'attendance-materi/contoh.pdf']);

        $this->actingAs($pengurus)->delete("/attendance/{$session->id}");

        Storage::disk('public')->assertMissing('attendance-materi/contoh.pdf');
    }

    public function test_show_page_lists_active_members_for_manual_entry(): void
    {
        $this->actingAs($this->makeUser('Pengurus'));
        $session = $this->makeSession();
        $aktif = $this->makeMember();

        Member::create([
            'ambalan_id' => $aktif->ambalan_id,
            'user_id' => $this->makeUser('Anggota')->id,
            'nta' => '31082008.030.000',
            'angkatan' => '030',
            'nomor_urut' => 1,
            'nta_username' => 'nonaktif1',
            'nama_lengkap' => 'Anggota Nonaktif',
            'kelas' => '-',
            'tingkatan' => 'SMA',
            'status_aktif' => 'Nonaktif',
            'no_hp' => '-',
        ]);

        $this->get("/attendance/{$session->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('members', fn ($members) => $this->memberNamesMatch($members))
            );
    }

    /** Hanya anggota aktif boleh ditawarkan untuk presensi manual. */
    private function memberNamesMatch($members): bool
    {
        $names = collect($members)->pluck('nama_lengkap');

        return $names->contains('Anggota Uji') && ! $names->contains('Anggota Nonaktif');
    }
}
