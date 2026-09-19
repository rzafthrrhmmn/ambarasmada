<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\PengurusPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class LetterGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->ambalan = Ambalan::create(['nama' => 'Test Ambalan', 'kode' => 'TEST']);
        $this->pengurusUser = User::factory()->create(['role' => 'Pengurus']);
        $this->pembinaUser = User::factory()->create(['role' => 'Pembina']);
        $this->adminUser = User::factory()->create(['role' => 'Admin']);
        $this->anggotaUser = User::factory()->create(['role' => 'Anggota']);
    }

    protected function createTemplateWithPlaceholders(): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $placeholders = [
            'nomor_surat', 'perihal', 'isi_surat', 'tujuan_pengirim',
            'tgl_surat', 'waktu_kegiatan', 'lokasi_kegiatan',
            'nama_ambalan', 'tanggal', 'jenis_surat',
            'nama_pradana_putra', 'nis_pradana_putra',
            'nama_pradana_putri', 'nis_pradana_putri',
        ];

        foreach ($placeholders as $ph) {
            $section->addText('${'.$ph.'}');
        }

        $tempPath = storage_path('app/public/test-template.docx');
        @mkdir(dirname($tempPath), 0755, true);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return $tempPath;
    }

    public function test_pengurus_can_generate_docx_with_valid_template(): void
    {
        $templatePath = $this->createTemplateWithPlaceholders();
        $storedPath = Storage::disk('public')->putFile('letter-templates', new UploadedFile($templatePath, 'template.docx', null, null, true));

        $template = LetterTemplate::create([
            'name' => 'Test Template',
            'file_path' => $storedPath,
            'description' => 'Test',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '001/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Test Perihal',
            'isi_surat' => 'Isi surat test',
            'tujuan_pengirim' => 'Tujuan Test',
            'tgl_surat' => '2026-01-15',
            'waktu_kegiatan' => '10:00',
            'lokasi_kegiatan' => 'Ruang Rapat',
            'template_id' => $template->id,
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->pengurusUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->assertHeaderContains('Content-Disposition', 'surat-Test Perihal.docx');
    }

    public function test_pengurus_can_generate_docx_without_template(): void
    {
        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '002/TEST/2026',
            'jenis_surat' => 'Masuk',
            'perihal' => 'Test Tanpa Template',
            'isi_surat' => 'Isi surat tanpa template',
            'tujuan_pengirim' => 'Pengirim Test',
            'tgl_surat' => '2026-01-15',
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->pengurusUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_admin_can_generate_docx(): void
    {
        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '003/TEST/2026',
            'jenis_surat' => 'Keputusan',
            'perihal' => 'Test Admin',
            'isi_surat' => 'Isi admin',
            'tujuan_pengirim' => 'Admin',
            'tgl_surat' => '2026-01-15',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertOk();
    }

    public function test_pembina_can_generate_docx(): void
    {
        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '004/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Test Pembina',
            'isi_surat' => 'Isi pembina',
            'tujuan_pengirim' => 'Pembina',
            'tgl_surat' => '2026-01-15',
            'created_by_user_id' => $this->pembinaUser->id,
        ]);

        $this->actingAs($this->pembinaUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertOk();
    }

    public function test_anggota_cannot_generate_docx(): void
    {
        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '005/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Test Anggota',
            'isi_surat' => 'Isi anggota',
            'tujuan_pengirim' => 'Anggota',
            'tgl_surat' => '2026-01-15',
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->anggotaUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertForbidden();
    }

    public function test_generate_docx_fails_when_template_file_missing(): void
    {
        $template = LetterTemplate::create([
            'name' => 'Missing Template',
            'file_path' => 'letter-templates/missing.docx',
            'description' => 'Test',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '006/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Test Missing',
            'isi_surat' => 'Isi missing',
            'tujuan_pengirim' => 'Missing',
            'tgl_surat' => '2026-01-15',
            'template_id' => $template->id,
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->pengurusUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertStatus(500);
    }

    public function test_generate_pdf_works(): void
    {
        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '007/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Test PDF',
            'isi_surat' => 'Isi PDF test',
            'tujuan_pengirim' => 'PDF Test',
            'tgl_surat' => '2026-01-15',
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->pengurusUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=pdf");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_template_upload_validates_placeholders(): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Template tanpa placeholder');

        $tempPath = storage_path('app/public/bad-template.docx');
        @mkdir(dirname($tempPath), 0755, true);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $this->actingAs($this->adminUser);
        $response = $this->post('/letters/templates', [
            'name' => 'Bad Template',
            'description' => 'Test',
            'file' => new UploadedFile($tempPath, 'bad-template.docx', null, null, true),
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Placeholder hilang', session('error'));
    }

    public function test_template_upload_accepts_valid_template(): void
    {
        $templatePath = $this->createTemplateWithPlaceholders();

        $this->actingAs($this->adminUser);
        $response = $this->post('/letters/templates', [
            'name' => 'Valid Template',
            'description' => 'Test',
            'file' => new UploadedFile($templatePath, 'valid-template.docx', null, null, true),
        ]);

        $response->assertRedirect(route('letters.templates'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('letter_templates', ['name' => 'Valid Template']);
    }

    public function test_generate_docx_includes_pradana_names_when_ambalan_has_positions(): void
    {
        $pradanaPutraPos = PengurusPosition::create(['name' => 'Pradana Putra', 'code' => 'pradana_putra', 'description' => '']);
        $pradanaPutriPos = PengurusPosition::create(['name' => 'Pradana Putri', 'code' => 'pradana_putri', 'description' => '']);

        $putraUser = User::factory()->create(['role' => 'Pengurus']);
        $putriUser = User::factory()->create(['role' => 'Pengurus']);

        $putraMember = Member::create([
            'ambalan_id' => $this->ambalan->id,
            'user_id' => $putraUser->id,
            'nta' => '1111111111111111',
            'angkatan' => '001',
            'nomor_urut' => 1,
            'nta_username' => '1111111111111111',
            'nama_lengkap' => 'Pradana Putra Test',
            'kelas' => 'X',
            'tingkatan' => 'Bantara',
            'status_aktif' => 'Aktif',
        ]);

        MemberPosition::create([
            'member_id' => $putraMember->id,
            'position_id' => $pradanaPutraPos->id,
            'assigned_by_user_id' => $this->adminUser->id,
        ]);

        $putriMember = Member::create([
            'ambalan_id' => $this->ambalan->id,
            'user_id' => $putriUser->id,
            'nta' => '1111111111111112',
            'angkatan' => '001',
            'nomor_urut' => 2,
            'nta_username' => '1111111111111112',
            'nama_lengkap' => 'Pradana Putri Test',
            'kelas' => 'X',
            'tingkatan' => 'Bantara',
            'status_aktif' => 'Aktif',
        ]);

        MemberPosition::create([
            'member_id' => $putriMember->id,
            'position_id' => $pradanaPutriPos->id,
            'assigned_by_user_id' => $this->adminUser->id,
        ]);

        $templatePath = $this->createTemplateWithPlaceholders();
        $storedPath = Storage::disk('public')->putFile('letter-templates', new UploadedFile($templatePath, 'template.docx', null, null, true));

        $template = LetterTemplate::create([
            'name' => 'Template With Pradana',
            'file_path' => $storedPath,
            'description' => 'Test',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '008/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Test Pradana',
            'isi_surat' => 'Isi dengan pradana',
            'tujuan_pengirim' => 'Pradana Test',
            'tgl_surat' => '2026-01-15',
            'template_id' => $template->id,
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->pengurusUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_preview_endpoint_works(): void
    {
        $this->actingAs($this->pengurusUser);
        $response = $this->post('/letters/preview', [
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '009/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Preview Test',
            'isi_surat' => 'Isi preview',
            'tujuan_pengirim' => 'Preview',
            'tgl_surat' => '2026-01-15',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_preview_requires_perihal(): void
    {
        $this->actingAs($this->pengurusUser);
        $response = $this->post('/letters/preview', [
            'ambalan_id' => $this->ambalan->id,
            'jenis_surat' => 'Keluar',
            'isi_surat' => 'Isi preview',
            'tujuan_pengirim' => 'Preview',
            'tgl_surat' => '2026-01-15',
        ]);

        $response->assertSessionHasErrors('perihal');
    }

    public function test_generate_requires_perihal_and_isi_surat(): void
    {
        $letter = Letter::create([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '010/TEST/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => '',
            'isi_surat' => '',
            'tujuan_pengirim' => 'Test',
            'tgl_surat' => '2026-01-15',
            'created_by_user_id' => $this->pengurusUser->id,
        ]);

        $this->actingAs($this->pengurusUser);
        $response = $this->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Isi surat belum diisi.');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/public/test-template.docx'));
        @unlink(storage_path('app/public/bad-template.docx'));
        parent::tearDown();
    }
}
