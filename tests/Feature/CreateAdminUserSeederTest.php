<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * CreateAdminUserSeeder adalah alat bootstrap untuk database yang belum punya
 * akun admin. Migration-seeded sudah menyediakan akun admin, jadi test
 * menghapus lebih dulu agar seeder benar-benar dijalankan.
 */
class CreateAdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'abcdwxyz06@gmail.com';

    protected function setUp(): void
    {
        parent::setUp();

        Ambalan::create([
            'nama' => 'Ambalan UPT SMAN 2 Maros',
            'kode' => 'GUDPU',
            'alamat' => 'Maros',
            'status' => 'aktif',
        ]);

        // Pastikan tepat satu angkatan berjalan agar nomor NTA deterministik.
        // Nomor angkatan production berformat 3 digit (string kolom varchar(3)).
        Angkatan::query()->update(['is_active' => false, 'is_current' => false]);
        Angkatan::create([
            'angkatan' => '2026',
            'nomor' => '030',
            'nama' => 'Angkatan 30',
            'is_active' => true,
            'is_current' => true,
        ]);

        $this->resetBootstrap();
    }

    /**
     * Buang akun admin hasil migration beserta record Member-nya, sehingga
     * seeder benar-benar dijalankan.
     */
    private function resetBootstrap(): void
    {
        $admin = User::where('email', self::ADMIN_EMAIL)->first();

        if ($admin) {
            Member::where('user_id', $admin->id)->forceDelete();
            $admin->delete();
        }
    }

    private function runSeeder(): string
    {
        $code = Artisan::call('db:seed', ['--class' => 'CreateAdminUserSeeder']);

        $this->assertSame(0, $code, 'Seeder gagal dijalankan.');

        return trim(Artisan::output());
    }

    public function test_seeder_uses_email_as_username(): void
    {
        $this->runSeeder();

        $user = User::where('email', self::ADMIN_EMAIL)->first();

        $this->assertNotNull($user, 'Seeder tidak membuat akun admin.');
        $this->assertSame(self::ADMIN_EMAIL, $user->username);
        $this->assertSame('Admin', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotSame('password123', $user->password);
    }

    public function test_seeder_assigns_an_nta_to_member_record(): void
    {
        $this->runSeeder();

        $user = User::where('email', self::ADMIN_EMAIL)->firstOrFail();
        $member = Member::where('user_id', $user->id)->first();

        $this->assertNotNull($member, 'Record Member tidak dibuat.');

        $prefix = (string) config('app.gudep_prefix', '31082008');
        $this->assertSame($prefix.'.030.001', $member->nta);

        // Username adalah email, NTA harus terpisah di tabel members.
        $this->assertNotSame($user->username, $member->nta);
        $this->assertSame($member->nta, $member->nta_username);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->runSeeder();
        $this->runSeeder();

        $this->assertSame(1, User::count());
        $this->assertSame(1, Member::count());
    }

    public function test_seeder_continues_nta_from_members_not_from_usernames(): void
    {
        $this->runSeeder();

        $veteran = User::create([
            'username' => 'veteran@example.test',
            'name' => 'Anggota Veteran',
            'email' => 'veteran@example.test',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        // Member tambahan pemilik NTA .030.005, username-nya email (bukan NTA).
        Member::create([
            'ambalan_id' => Ambalan::first()->id,
            'user_id' => $veteran->id,
            'nta' => '31082008.030.005',
            'nta_username' => '31082008.030.005',
            'angkatan' => '030',
            'nomor_urut' => 5,
            'nama_lengkap' => 'Anggota Veteran',
            'kelas' => 'XII',
            'tingkatan' => 'Laksana',
            'status_aktif' => 'Aktif',
        ]);

        $this->resetBootstrap();
        $this->runSeeder();

        $member = Member::where('nta', 'like', '31082008.030.%')
            ->orderByDesc('nomor_urut')
            ->first();

        $this->assertNotNull($member);
        $this->assertSame(
            6,
            (int) $member->nomor_urut,
            'Counter NTA harus membaca tabel members, bukan users.username.'
        );
    }
}
