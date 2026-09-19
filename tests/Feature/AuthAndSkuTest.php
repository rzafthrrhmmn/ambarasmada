<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Finance;
use App\Models\Member;
use App\Models\SkuPoint;
use App\Models\SkuSubmission;
use App\Models\User;
use Database\Seeders\SkuPenegakPointSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndSkuTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_cannot_open_operational_menu(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Ambalan UPT SMAN 2 Maros', 'kode' => 'TEST']);
        $alumniUser = User::factory()->create(['role' => 'Alumni']);
        Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $alumniUser->id,
            'nta' => '1111111111111112',
            'nama_lengkap' => 'Alumni Uji',
            'kelas' => 'XII IPA 1',
            'tingkatan' => 'Laksana',
            'tahun_lulus' => 2025,
            'status_aktif' => 'Alumni',
        ]);

        $this->actingAs($alumniUser)
            ->get(route('members.index'))
            ->assertForbidden();

        $this->actingAs($alumniUser)
            ->get(route('alumni.dashboard'))
            ->assertOk();
    }

    public function test_sku_penegak_seeder_provides_official_point_counts(): void
    {
        $this->seed(SkuPenegakPointSeeder::class);

        $this->assertSame(23, SkuPoint::where('tingkatan', 'Bantara')->where('is_active', true)->count());
        $this->assertSame(22, SkuPoint::where('tingkatan', 'Laksana')->where('is_active', true)->count());
        $this->assertSame(0, SkuPoint::where('tingkatan', 'Penegak')->where('is_active', true)->count());

        foreach (['Bantara', 'Laksana'] as $level) {
            foreach ([1, 2] as $number) {
                $description = SkuPoint::query()
                    ->where('tingkatan', $level)
                    ->where('nomor_poin', $number)
                    ->value('deskripsi_poin');

                $this->assertStringContainsString('Islam:', $description);
                $this->assertStringNotContainsString('Katolik', $description);
                $this->assertStringNotContainsString('Protestan', $description);
                $this->assertStringNotContainsString('Hindu', $description);
                $this->assertStringNotContainsString('Buddha', $description);
            }
        }
    }

    public function test_anggota_can_submit_sku_with_documentation(): void
    {
        Storage::fake('public');

        $ambalan = Ambalan::create(['nama' => 'Ambalan UPT SMAN 2 Maros', 'kode' => 'TEST']);
        $memberUser = User::factory()->create(['role' => 'Anggota']);
        $member = Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $memberUser->id,
            'nta' => '1111111111111111',
            'nama_lengkap' => 'Anggota Uji',
            'kelas' => 'XI IPA 1',
            'tingkatan' => 'Bantara',
            'status_aktif' => 'Aktif',
        ]);
        $point = SkuPoint::create([
            'tingkatan' => 'Bantara',
            'nomor_poin' => 1,
            'deskripsi_poin' => 'Poin uji',
            'is_active' => true,
        ]);
        $evidence = UploadedFile::fake()->image('bukti.jpg');
        $photo = UploadedFile::fake()->image('foto.jpg');

        $this->actingAs($memberUser)
            ->post(route('sku.store'), [
                'sku_point_id' => $point->id,
                'description' => 'Bukti kegiatan',
                'bukti_kegiatan' => $evidence,
                'foto' => [$photo],
            ])
            ->assertRedirect(route('sku.index'));

        $submission = SkuSubmission::firstOrFail();
        $this->assertSame('Pending', $submission->status);
        $this->assertSame($member->id, $submission->member_id);
        $this->assertSame($point->id, $submission->sku_point_id);
        $this->assertNotNull($submission->bukti_kegiatan);
        Storage::disk('public')->assertExists($submission->bukti_kegiatan);
        $this->assertSame(1, $submission->media()->count());
    }

    public function test_sku_submission_requires_documentation(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Ambalan UPT SMAN 2 Maros', 'kode' => 'TEST']);
        $memberUser = User::factory()->create(['role' => 'Anggota']);
        Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $memberUser->id,
            'nta' => '1111111111111111',
            'nama_lengkap' => 'Anggota Uji',
            'kelas' => 'XI IPA 1',
            'tingkatan' => 'Bantara',
            'status_aktif' => 'Aktif',
        ]);
        $point = SkuPoint::create([
            'tingkatan' => 'Bantara',
            'nomor_poin' => 1,
            'deskripsi_poin' => 'Poin uji',
            'is_active' => true,
        ]);

        $this->actingAs($memberUser)
            ->post(route('sku.store'), [
                'sku_point_id' => $point->id,
                'description' => 'Bukti kegiatan',
            ])
            ->assertSessionHasErrors('bukti_kegiatan');

        $this->assertDatabaseMissing('sku_submissions', ['sku_point_id' => $point->id]);
    }

    public function test_pembina_can_approve_sku_submission(): void
    {
        Storage::fake('public');

        $ambalan = Ambalan::create(['nama' => 'Ambalan UPT SMAN 2 Maros', 'kode' => 'TEST']);
        $memberUser = User::factory()->create(['role' => 'Anggota']);
        $pembina = User::factory()->create(['role' => 'Pembina']);
        $member = Member::create([
            'ambalan_id' => $ambalan->id,
            'user_id' => $memberUser->id,
            'nta' => '1111111111111111',
            'nama_lengkap' => 'Anggota Uji',
            'kelas' => 'XI IPA 1',
            'tingkatan' => 'Bantara',
            'status_aktif' => 'Aktif',
        ]);
        $point = SkuPoint::create([
            'tingkatan' => 'Bantara',
            'nomor_poin' => 1,
            'deskripsi_poin' => 'Poin uji',
            'is_active' => true,
        ]);

        $this->actingAs($memberUser)
            ->post(route('sku.store'), [
                'sku_point_id' => $point->id,
                'description' => 'Bukti kegiatan',
                'bukti_kegiatan' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('sku.index'));

        $submission = SkuSubmission::firstOrFail();

        $this->actingAs($pembina)
            ->post(route('sku.approve', $submission), ['catatan' => 'Lolos'])
            ->assertRedirect(route('sku.index'));

        $this->assertSame($member->id, $submission->member_id);
        $this->assertDatabaseHas('sku_submissions', [
            'id' => $submission->id,
            'status' => 'Approved',
            'verified_by' => $pembina->id,
        ]);
    }

    public function test_non_anggota_cannot_submit_sku(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Ambalan UPT SMAN 2 Maros', 'kode' => 'TEST']);
        $pembina = User::factory()->create(['role' => 'Pembina']);

        $this->actingAs($pembina)
            ->post(route('sku.store'), [
                'sku_point_id' => 1,
                'description' => 'Bukti kegiatan',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('sku_submissions', [
            'status' => 'Pending',
        ]);
    }

    public function test_posted_finance_transaction_is_immutable(): void
    {
        $ambalan = Ambalan::create(['nama' => 'Ambalan UPT SMAN 2 Maros', 'kode' => 'TEST']);
        $user = User::factory()->create(['role' => 'Admin']);
        $finance = Finance::create([
            'ambalan_id' => $ambalan->id,
            'jenis_transaksi' => 'Masuk',
            'nominal' => 10000,
            'keterangan' => 'Kas latihan',
            'created_by' => $user->id,
            'tgl_transaksi' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $this->actingAs($user)
            ->post(route('finance.post', $finance))
            ->assertRedirect(route('finance.index'));

        $this->actingAs($user)
            ->patch(route('finance.update', $finance), [
                'jenis_transaksi' => 'Masuk',
                'nominal' => 20000,
                'keterangan' => 'Salah nominal',
                'tgl_transaksi' => now()->toDateString(),
            ])
            ->assertStatus(422);
    }
}
