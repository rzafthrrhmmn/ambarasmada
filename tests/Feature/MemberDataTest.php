<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_pembina_sees_members_in_index(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Test Ambalan', 'kode' => 'TEST']);
        $pembina = User::factory()->create(['role' => 'Pembina', 'email' => 'pembina@test.com']);

        Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $pembina->id,
            'nta' => '1111111111111111',
            'angkatan' => '111',
            'nomor_urut' => 1,
            'nta_username' => '1111111111111111',
            'nama_lengkap' => 'Pembina Ambalan',
            'kelas' => '-',
            'tingkatan' => '-',
            'tahun_lulus' => null,
            'status_aktif' => 'Aktif',
        ]);

        $u2 = User::factory()->create(['role' => 'Anggota', 'email' => 'reza@test.com']);
        Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $u2->id,
            'nta' => '1111111111111112',
            'angkatan' => '111',
            'nomor_urut' => 2,
            'nta_username' => '1111111111111112',
            'nama_lengkap' => 'Reza Fathurrahman',
            'kelas' => 'XII IPA 1',
            'tingkatan' => 'Bantara',
            'tahun_lulus' => 2025,
            'status_aktif' => 'Aktif',
        ]);

        $this->assertDatabaseHas('members', ['nama_lengkap' => 'Pembina Ambalan']);
        $this->assertDatabaseHas('members', ['nama_lengkap' => 'Reza Fathurrahman']);

        $response = $this->actingAs($pembina)
            ->get(route('members.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Members/Index')
            ->has('members.data', 2)
        );
    }
}
