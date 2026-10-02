<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Finance;
use App\Models\HealthRecord;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kebocoran data yang ditutup per modul.
 *
 * Setiap metode di bawah ini pernah mengirim data yang seharusnya tidak keluar:
 *
 * - FinanceController menjumlahkan kas seluruh ambalan tanpa filter dan hanya
 *   menyembunyikannya di tampilan, jadi angkanya tetap ada di payload.
 * - Predicate penyempitannya hanya cocok untuk Anggota dan Pengurus biasa,
 *   sehingga peran lain otomatis menerima seluruh buku kas.
 * - HealthSafetyController tidak memfilter ambalan_id padahal tabelnya punya
 *   kolom itu, jadi rekam medis bisa dibaca lintas ambalan.
 * - RegistrationController::pendingUsers tidak punya guard sendiri.
 */
class DataLeakPreventionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $suffix = Str::lower($role).'.'.uniqid().'@test.local';

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

    private function makeAmbalan(string $kode = 'AMB'): Ambalan
    {
        return Ambalan::first() ?: Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => $kode,
            'status' => 'aktif',
        ]);
    }

    private function makeMember(?User $user, ?Ambalan $ambalan, string $nama = 'Anggota'): Member
    {
        return Member::create([
            'ambalan_id' => ($ambalan ?? $this->makeAmbalan())->id,
            'user_id' => ($user ?? $this->makeUser('Anggota'))->id,
            'nta' => '31082008.030.'.random_int(100, 999),
            'angkatan' => '030',
            'nomor_urut' => random_int(100, 999),
            'nta_username' => 'x'.random_int(1000, 9999),
            'nama_lengkap' => $nama,
            'kelas' => 'XII IPA 1',
            'tingkatan' => 'Laksana',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);
    }

    private function makeFinance(Member $member, int $nominal, string $jenis = 'Masuk'): Finance
    {
        return Finance::create([
            'member_id' => $member->id,
            // finances.created_by NOT NULL; diisi user_id anggota supaya
            // pembuatan baris tidak bergantung pada akun pengelola.
            'created_by' => $member->user_id,
            'jenis_transaksi' => $jenis,
            'nominal' => $nominal,
            'tgl_transaksi' => now(),
            'status' => 'Posted',
            'keterangan' => 'Iuran uji',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Keuangan */
    /* ------------------------------------------------------------------ */

    public function test_anggota_tidak_menerima_daftar_anggota_aktif(): void
    {
        $member = $this->makeMember(null, null, 'Anggota yang Login');
        $this->makeMember(null, $this->makeAmbalan(), 'Anggota Orang Lain');

        $payload = $this->actingAs($member->user)
            ->get('/finance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertCount(0, $payload['members'], 'Direktori anggota tidak boleh dikirim ke anggota.');
    }

    public function test_anggota_hanya_menerima_transaksi_miliknya(): void
    {
        $member = $this->makeMember(null, null, 'Anggota yang Login');
        $other = $this->makeMember(null, $this->makeAmbalan(), 'Anggota Orang Lain');

        $this->makeFinance($member, 50_000);
        $this->makeFinance($other, 999_000);

        $payload = $this->actingAs($member->user)
            ->get('/finance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(50_000, (int) $payload['income']);
        $this->assertSame(50_000, (int) $payload['balance']);
        $this->assertStringNotContainsString('999.000', json_encode($payload['transactions']));
        $this->assertStringNotContainsString('Anggota Orang Lain', json_encode($payload));
    }

    public function test_peran_lain_tidak_menerima_buku_kas_penuh(): void
    {
        // Admin dan Pembina adalah pengelola, Anggota dan Pengurus biasa bukan.
        // Yang diuji adalah generalised default-deny di controller: siapa pun
        // yang bukan pengelola hanya melihat akunnya sendiri.
        //
        // Alumni sendiri sudah dialihkan middleware EnsureNotAlumni ke portal
        // alumni sebelum mencapai controller, jadi tidak bisa dipakai sebagai
        // kasus uji di sini.
        $pengurus = $this->makeUser('Pengurus');
        $pengurus->member()->create([
            'ambalan_id' => $this->makeAmbalan()->id,
            'nta' => '31082008.030.901',
            'angkatan' => '030',
            'nomor_urut' => 901,
            'nta_username' => 'x9011',
            'nama_lengkap' => 'Pengurus Biasa',
            'kelas' => '-',
            'tingkatan' => '-',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);

        $memberLain = $this->makeMember(null, $this->makeAmbalan(), 'Anggota yang Login');
        $this->makeFinance($memberLain, 750_000);

        $payload = $this->actingAs($pengurus)
            ->get('/finance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(0, (int) $payload['income']);
        $this->assertSame(0, (int) $payload['balance']);
        $this->assertCount(0, $payload['transactions']['data']);
        $this->assertCount(0, $payload['members']);
    }

    public function test_alumni_dialihkan_keluar_dari_keuangan(): void
    {
        // Penjaga terakhir untuk peran yang tidak punya baris member. Tanpa
        // ini, perubahan di controller tidak akan pernah menutupi peran baru
        // yang belum dikenali middleware.
        $alumni = $this->makeUser('Alumni');
        $this->makeFinance($this->makeMember(null, null), 750_000);

        $this->actingAs($alumni)
            ->get('/finance')
            ->assertRedirect(route('alumni.dashboard'));
    }

    public function test_pengelola_tetap_menerima_seluruh_kas(): void
    {
        $pengelola = $this->makeUser('Pembina');
        $satu = $this->makeMember(null, null, 'Anggota Satu');
        $dua = $this->makeMember(null, $this->makeAmbalan(), 'Anggota Dua');

        $this->makeFinance($satu, 100_000);
        $this->makeFinance($dua, 250_000, 'Keluar');

        $payload = $this->actingAs($pengelola)
            ->get('/finance')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(100_000, (int) $payload['income']);
        $this->assertSame(250_000, (int) $payload['expense']);
        $this->assertSame(-150_000, (int) $payload['balance']);
        $this->assertCount(2, $payload['members']);
    }

    /* ------------------------------------------------------------------ */
    /* Rekam medis */
    /* ------------------------------------------------------------------ */

    public function test_rekam_medis_lintas_ambalan_tidak_bisa_dibaca(): void
    {
        $ambalanAsal = $this->makeAmbalan('ASAL');
        $ambalanAsing = Ambalan::create([
            'nama' => 'Ambalan Asing',
            'kode' => 'ASG',
            'status' => 'aktif',
        ]);

        $pengelola = $this->makeUser('Pengurus');
        $pengelola->member()->create([
            'ambalan_id' => $ambalanAsal->id,
            'nta' => '31082008.030.111',
            'angkatan' => '030',
            'nomor_urut' => 111,
            'nta_username' => 'x1111',
            'nama_lengkap' => 'Pengelola',
            'kelas' => '-',
            'tingkatan' => '-',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);

        $memberAsal = $this->makeMember(null, $ambalanAsal, 'Anggota Asal');
        $memberAsing = $this->makeMember(null, $ambalanAsing, 'Anggota Asing');

        HealthRecord::create([
            'ambalan_id' => $ambalanAsal->id,
            'member_id' => $memberAsal->id,
            'created_by' => $pengelola->id,
            'alergi' => 'Alergi Khas Ambalan Asal',
        ]);

        HealthRecord::create([
            'ambalan_id' => $ambalanAsing->id,
            'member_id' => $memberAsing->id,
            'created_by' => $pengelola->id,
            'alergi' => 'Alergi Khas Ambalan Asing',
        ]);

        $payload = $this->actingAs($pengelola)
            ->get('/health/safety/records')
            ->assertOk()
            ->viewData('page')['props'];

        $serialized = json_encode($payload['records']);

        $this->assertStringContainsString('Alergi Khas Ambalan Asal', $serialized);
        $this->assertStringNotContainsString(
            'Alergi Khas Ambalan Asing',
            $serialized,
            'Rekam medis ambalan lain tidak boleh ikut terkirim.'
        );
        $this->assertStringNotContainsString('Anggota Asing', json_encode($payload['allMembers']));
    }

    public function test_halaman_rekam_medis_mengirim_daftar_anggota(): void
    {
        $ambalan = $this->makeAmbalan();
        $pengelola = $this->makeUser('Pengurus');
        $pengelola->member()->create([
            'ambalan_id' => $ambalan->id,
            'nta' => '31082008.030.222',
            'angkatan' => '030',
            'nomor_urut' => 222,
            'nta_username' => 'x2222',
            'nama_lengkap' => 'Pengelola',
            'kelas' => '-',
            'tingkatan' => '-',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);

        $member = $this->makeMember(null, $ambalan, 'Anggota Terpilih');

        // Tanpa prop ini, dropdown "pilih anggota" kosong dan member_id yang
        // wajib diisi tidak pernah bisa terisi.
        $this->actingAs($pengelola)
            ->get('/health/safety/records')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('HealthSafety/HealthRecords')
                ->has('allMembers')
            )
            ->assertSee('Anggota Terpilih');
    }

    public function test_rekam_medis_ambalan_lain_tidak_bisa_diubah_atau_dihapus(): void
    {
        $ambalanAsal = $this->makeAmbalan('ASAL');
        $ambalanAsing = Ambalan::create(['nama' => 'Asing', 'kode' => 'ASG', 'status' => 'aktif']);

        $pengelola = $this->makeUser('Pengurus');
        $pengelola->member()->create([
            'ambalan_id' => $ambalanAsal->id,
            'nta' => '31082008.030.333',
            'angkatan' => '030',
            'nomor_urut' => 333,
            'nta_username' => 'x3333',
            'nama_lengkap' => 'Pengelola',
            'kelas' => '-',
            'tingkatan' => '-',
            'status_aktif' => 'Aktif',
            'no_hp' => '-',
        ]);

        $record = HealthRecord::create([
            'ambalan_id' => $ambalanAsing->id,
            'member_id' => $this->makeMember(null, $ambalanAsing, 'Anggota Asing')->id,
            'created_by' => $pengelola->id,
            'alergi' => 'Tidak Boleh Disentuh',
        ]);

        $this->actingAs($pengelola)
            ->patch("/health/safety/records/{$record->id}", ['alergi' => 'Berubah'])
            ->assertForbidden();

        $this->actingAs($pengelola)
            ->delete("/health/safety/records/{$record->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('health_records', ['id' => $record->id, 'alergi' => 'Tidak Boleh Disentuh']);
    }

    public function test_anggota_tidak_boleh_membuka_halaman_rekam_medis(): void
    {
        $member = $this->makeMember(null, null);

        $this->actingAs($member->user)
            ->get('/health/safety/records')
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------ */
    /* Antrean verifikasi pendaftaran */
    /* ------------------------------------------------------------------ */

    public function test_daftar_pendaftar_menolak_anggota(): void
    {
        $member = $this->makeMember(null, null);

        $this->actingAs($member->user)
            ->get('/members/pending')
            ->assertForbidden();
    }

    public function test_daftar_pendaftar_bisa_diakses_pembina(): void
    {
        $pembina = $this->makeUser('Pembina');
        $pending = $this->makeUser('Anggota');
        $pending->update(['status' => 'pending']);

        $this->actingAs($pembina)
            ->get('/members/pending')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Members/PendingUsers')
                ->has('pendingUsers')
            );
    }

    /* ------------------------------------------------------------------ */
    /* Laporan dan angkatan */
    /* ------------------------------------------------------------------ */

    public function test_halaman_laporan_bisa_diakses(): void
    {
        // Route /reports tidak pernah ada, padahal sidebar menautkan ke sana.
        $this->actingAs($this->makeUser('Pembina'))
            ->get('/reports')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Reports/Index'));
    }

    public function test_halaman_laporan_menolak_anggota(): void
    {
        $this->actingAs($this->makeMember(null, null)->user)
            ->get('/reports')
            ->assertForbidden();
    }

    public function test_daftar_angkatan_mengirim_jumlah_anggota(): void
    {
        $pengelola = $this->makeUser('Pengurus');
        $ambalan = $this->makeAmbalan();

        $angkatan = Angkatan::create([
            'ambalan_id' => $ambalan->id,
            'angkatan' => '030',
            // Angka pada kolom nomor inilah yang dicocokkan ke kolom
            // members.angkatan oleh relasi Angkatan::members().
            'nomor' => '030',
            'nama' => 'Angkatan Uji',
            'is_active' => true,
            'is_current' => true,
        ]);

        $this->makeMember(null, $ambalan, 'Anggota Satu');
        $this->makeMember(null, $ambalan, 'Anggota Dua');

        $payload = $this->actingAs($pengelola)
            ->get('/angkatan')
            ->assertOk()
            ->viewData('page')['props'];

        $row = collect($payload['angkatan']['data'])->firstWhere('angkatan', '030');

        $this->assertNotNull($row);
        $this->assertSame(2, (int) $row['members_count'], 'Jumlah anggota angkatan harus ikut terhitung.');
    }
}
