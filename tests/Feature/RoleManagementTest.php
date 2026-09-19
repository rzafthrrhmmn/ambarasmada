<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_pembina_can_create_angkatan(): void
    {
        $pembina = User::factory()->create(['role' => 'Pembina']);
        $this->actingAs($pembina);

        $response = $this->post('/angkatan', [
            'tahun' => '2026',
            'nomor' => '018',
            'nama' => 'Angkatan 018 - 2026',
            'is_current' => true,
        ]);

        $response->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('angkatans', [
            'angkatan' => '2026',
            'nomor' => '018',
            'nama' => 'Angkatan 018 - 2026',
            'is_current' => true,
        ]);
    }

    public function test_anggota_cannot_create_angkatan(): void
    {
        $anggota = User::factory()->create(['role' => 'Anggota']);
        $this->actingAs($anggota);

        $response = $this->post('/angkatan', [
            'angkatan' => '011',
            'nama' => 'Angkatan 011',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('angkatans', ['angkatan' => '011']);
    }

    public function test_pembina_can_bulk_change_role(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Test', 'kode' => 'TEST']);
        $pembina = User::factory()->create(['role' => 'Pembina']);
        $anggota1 = User::factory()->create(['role' => 'Anggota']);
        $anggota2 = User::factory()->create(['role' => 'Anggota']);

        Member::create(['ambalan_id' => $ambalan->id, 'user_id' => $anggota1->id, 'nta' => '1111111111111111', 'angkatan' => '010', 'nomor_urut' => 1, 'nta_username' => '1111111111111111', 'nama_lengkap' => 'A', 'kelas' => 'X', 'tingkatan' => 'Bantara', 'status_aktif' => 'Aktif']);
        Member::create(['ambalan_id' => $ambalan->id, 'user_id' => $anggota2->id, 'nta' => '1111111111111112', 'angkatan' => '010', 'nomor_urut' => 2, 'nta_username' => '1111111111111112', 'nama_lengkap' => 'B', 'kelas' => 'X', 'tingkatan' => 'Bantara', 'status_aktif' => 'Aktif']);

        Angkatan::create(['angkatan' => '010', 'nomor' => '010', 'nama' => 'Angkatan 010', 'is_active' => true]);

        $this->actingAs($pembina);

        $response = $this->post('/members/bulk-change-role', [
            'angkatan' => '010',
            'new_role' => 'Pengurus',
        ]);

        $response->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('users', ['id' => $anggota1->id, 'role' => 'Pengurus']);
        $this->assertDatabaseHas('users', ['id' => $anggota2->id, 'role' => 'Pengurus']);
    }

    public function test_anggota_cannot_bulk_change_role(): void
    {
        $anggota = User::factory()->create(['role' => 'Anggota']);
        $this->actingAs($anggota);

        $response = $this->post('/members/bulk-change-role', [
            'angkatan' => '010',
            'new_role' => 'Pengurus',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_bulk_change_role(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $response = $this->post('/members/bulk-change-role', [
            'angkatan' => '010',
            'new_role' => 'Pengurus',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_letters(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $response = $this->get('/letters');

        $response->assertForbidden();
    }

    public function test_pembina_can_access_letters(): void
    {
        $pembina = User::factory()->create(['role' => 'Pembina']);
        $this->actingAs($pembina);

        $response = $this->get('/letters');

        $response->assertOk();
    }

    public function test_pengurus_can_access_letters(): void
    {
        $pengurus = User::factory()->create(['role' => 'Pengurus']);
        $this->actingAs($pengurus);

        $response = $this->get('/letters');

        $response->assertOk();
    }

    public function test_anggota_cannot_access_letters(): void
    {
        $anggota = User::factory()->create(['role' => 'Anggota']);
        $this->actingAs($anggota);

        $response = $this->get('/letters');

        $response->assertForbidden();
    }

    public function test_pembina_can_set_current_angkatan_and_registration_uses_its_number(): void
    {
        Ambalan::create(['nama' => 'Test', 'kode' => 'TEST']);
        $pembina = User::factory()->create(['role' => 'Pembina']);
        Angkatan::create([
            'angkatan' => '2025',
            'nomor' => '017',
            'nama' => 'Angkatan 2025',
            'is_active' => true,
            'is_current' => true,
        ]);

        $this->actingAs($pembina);
        $response = $this->post('/angkatan', [
            'tahun' => '2026',
            'nomor' => '018',
            'nama' => 'Angkatan 018 - 2026',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('angkatans', ['angkatan' => '2025', 'is_current' => false]);
        $this->assertDatabaseHas('angkatans', ['angkatan' => '2026', 'nomor' => '018', 'is_current' => true]);

        $this->post('/register', [
            'nama_lengkap' => 'Anggota Baru',
            'email' => 'baru@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'username' => '31082008.018.001',
            'role' => 'Anggota',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('members', [
            'angkatan' => '018',
            'nta' => '31082008.018.001',
        ]);
    }

    public function test_archiving_current_angkatan_preserves_members_and_selects_replacement(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Test', 'kode' => 'TEST']);
        $pembina = User::factory()->create(['role' => 'Pembina']);
        $replacement = Angkatan::create([
            'angkatan' => '2025',
            'nomor' => '017',
            'nama' => 'Angkatan 2025',
            'is_active' => true,
            'is_current' => false,
        ]);
        $current = Angkatan::create([
            'angkatan' => '2026',
            'nomor' => '018',
            'nama' => 'Angkatan 2026',
            'is_active' => true,
            'is_current' => true,
        ]);
        $memberUser = User::factory()->create(['role' => 'Anggota']);
        Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $memberUser->id,
            'nta' => '31082008.018.001',
            'angkatan' => '018',
            'nomor_urut' => 1,
            'nta_username' => '31082008.018.001',
            'nama_lengkap' => 'Anggota Lama',
            'kelas' => 'X',
            'tingkatan' => 'Tamu',
            'status_aktif' => 'Aktif',
        ]);

        $this->actingAs($pembina);
        $response = $this->delete("/angkatan/{$current->id}");

        $response->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('angkatans', [
            'id' => $current->id,
            'is_active' => false,
            'is_current' => false,
        ]);
        $this->assertDatabaseHas('members', ['user_id' => $memberUser->id, 'angkatan' => '018']);
        $this->assertTrue($replacement->fresh()->is_current);
    }

    public function test_pembina_can_update_angkatan_identity_and_current_status(): void
    {
        $pembina = User::factory()->create(['role' => 'Pembina']);
        $angkatan = Angkatan::create([
            'angkatan' => '2026',
            'nomor' => '018',
            'nama' => 'Angkatan 2026',
            'is_active' => true,
            'is_current' => true,
        ]);

        $this->actingAs($pembina);
        $response = $this->patch("/angkatan/{$angkatan->id}", [
            'tahun' => '2027',
            'nomor' => '019',
            'nama' => 'Angkatan 019 - 2027',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('angkatans', [
            'id' => $angkatan->id,
            'angkatan' => '2027',
            'nomor' => '019',
            'nama' => 'Angkatan 019 - 2027',
            'is_current' => true,
        ]);
    }
}
