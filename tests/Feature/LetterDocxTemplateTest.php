<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

/**
 * Alur persuratan: unggah template .docx, buat surat dari template itu, lalu
 * ekspor ke .docx dan .pdf.
 *
 * Yang dijaga di sini adalah isi dokumen hasil ekspor, bukan hanya kode
 * respons. Penanda yang tidak terisi, karakter yang tidak di-escape, atau tag
 * yang hilang membuat .docx rusak dan tidak bisa dibuka di Word, dan itu baru
 * ketahuan setelah pengguna mengunduh berkasnya.
 */
class LetterDocxTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** Penanda yang dipakai semua fixture template di bawah. */
    private const PLACEHOLDERS = [
        'nomor_surat',
        'perihal',
        'isi_surat',
        'tujuan_pengirim',
        'tgl_surat',
        'nama_ambalan',
        'tanggal',
        'nama_pradana_putra',
        'nis_pradana_putra',
        'nisn_pradana_putra',
        'nip_pembina',
        'kode_kegiatan',
    ];

    private User $pengurus;

    private Ambalan $ambalan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->ambalan = Ambalan::create(['kode' => 'AMB', 'nama' => 'Ambalan Pramuka SMAN 2 Maros']);
        $this->pengurus = $this->createUser('Pengurus');
    }

    /**
     * Folder aplikasi hanya-baca di hosting Vercel, jadi template harus bisa
     * disimpan di disk remote (S3/Supabase) dan tetap terbaca untuk pratinjau
     * maupun ekspor.
     */
    public function test_template_bisa_disimpan_di_disk_remote(): void
    {
        Storage::fake('letters-remote');
        config(['letters.disk' => 'letters-remote']);

        $this->actingAs($this->pengurus)
            ->post('/letters/templates', ['name' => 'Surat Undangan', 'file' => $this->templateFile()])
            ->assertRedirect(route('letters.templates'))
            ->assertSessionHas('success');

        $template = LetterTemplate::firstOrFail();

        Storage::disk('letters-remote')->assertExists($template->file_path);
        Storage::disk('public')->assertMissing($template->file_path);
        $this->assertNotEmpty($template->placeholderList());

        $letter = $this->storeLetter($template);

        $text = $this->docxText($this->exportDocx($letter));

        $this->assertStringContainsString('421.1/045.34/PMR/2026', $text, 'Template dari disk remote tidak terisi saat ekspor .docx.');
        $this->assertStringNotContainsString('${', $text, 'Penanda kosong pada template dari disk remote.');

        $this->actingAs($this->pengurus)
            ->get(route('letters.templates.download', $template))
            ->assertOk();
    }

    /**
     * Inertia meminta 303 untuk POST/PATCH/DELETE supaya peramban mengikuti
     * dengan GET. Kalau 302, halaman dirender dua kali dengan metode yang sama.
     */
    public function test_redirect_setelah_tulis_memakai_303(): void
    {
        $template = $this->storeTemplate();

        $this->actingAs($this->pengurus)
            ->post('/letters/templates', ['name' => 'Template Baru', 'file' => $this->templateFile()])
            ->assertStatus(303);

        $this->storeLetter($template);

        $this->actingAs($this->pengurus)
            ->post('/letters', $this->letterPayload($template->id))
            ->assertStatus(303);

        $letter = Letter::firstOrFail();

        $this->actingAs($this->pengurus)
            ->delete("/letters/{$letter->id}")
            ->assertStatus(303);
    }

    /**
     * Template tetap bisa dihapus walau berkasnya tidak ada atau penyimpanannya
     * bermasalah, dan kegagalan menulis audit tidak membatalkan aksi pengguna.
     */
    public function test_hapus_template_tetap_berhasil_walau_gagal_akses_berkas(): void
    {
        $template = $this->storeTemplate();

        Storage::disk('public')->delete($template->file_path);

        $this->actingAs($this->pengurus)
            ->delete("/letters/templates/{$template->id}")
            ->assertRedirect(route('letters.templates'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('letter_templates', ['id' => $template->id]);
    }

    /**
     * Folder sementara di Vercel hilang tiap kali fungsi dinyalakan ulang, jadi
     * halaman template harus memperingatkan sebelum berkasnya hilang.
     */
    public function test_halaman_template_memperingatkan_disk_sementara(): void
    {
        config([
            'letters.disk' => 'sementara',
            'filesystems.disks.sementara' => ['driver' => 'local', 'root' => '/tmp/storage/app/public', 'throw' => false],
        ]);
        Storage::fake('sementara');

        $this->actingAs($this->pengurus)
            ->get('/letters/templates')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('storage.disk', 'sementara')
                ->where('storage.persistent', false)
                ->where('storage.writable', true)
            );
    }

    public function test_halaman_template_tanpa_peringatan_untuk_disk_yawet(): void
    {
        $this->actingAs($this->pengurus)
            ->get('/letters/templates')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('storage.disk', 'public')
                ->where('storage.persistent', true)
            );
    }

    public function test_template_yang_diunggah_penandanya_terdeteksi(): void
    {
        $this->actingAs($this->pengurus)
            ->post('/letters/templates', ['name' => 'Surat Keputusan', 'description' => 'SK Pradana', 'file' => $this->templateFile()])
            ->assertRedirect(route('letters.templates'))
            ->assertSessionHas('success');

        $template = LetterTemplate::firstOrFail();

        $this->assertSame('Surat Keputusan', $template->name);
        $this->assertEqualsCanonicalizing(self::PLACEHOLDERS, $template->placeholders);
        Storage::disk('public')->assertExists($template->file_path);
    }

    public function test_template_tanpa_penanda_ditolak_dan_berkas_tidak_tersimpan(): void
    {
        $this->actingAs($this->pengurus)
            ->post('/letters/templates', ['name' => 'Tanpa penanda', 'file' => $this->docxUpload($this->plainDocument())])
            ->assertSessionHas('error');

        $this->assertSame(0, LetterTemplate::count());
        $this->assertEmpty(Storage::disk('public')->files('letter-templates'));
    }

    public function test_berkas_bukan_docx_ditolak(): void
    {
        $this->actingAs($this->pengurus)
            ->post('/letters/templates', [
                'name' => 'Palsu',
                'file' => UploadedFile::fake()->createWithContent('palsu.docx', 'ini bukan dokumen word'),
            ])
            ->assertStatus(422);

        $this->assertSame(0, LetterTemplate::count());
    }

    public function test_anggota_tidak_bisa_mengelola_template(): void
    {
        $this->actingAs($this->createUser('Anggota'))
            ->post('/letters/templates', ['name' => 'Dilarang', 'file' => $this->templateFile()])
            ->assertForbidden();
    }

    public function test_halaman_surat_menampilkan_isian_dari_penanda_template(): void
    {
        $this->storeTemplate();

        $this->actingAs($this->pengurus)
            ->get('/letters')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Letters/Index')
                ->where('templates.0.name', 'Surat Keputusan')
                ->where('templates.0.fields.kode_kegiatan.label', 'Kode Kegiatan')
                ->where('templates.0.fields.nis_pradana_putra.label', 'NIS Pradana Putra')
                ->has('ambalans')
                ->has('jenisOptions')
            );
    }

    public function test_pratinjau_mengisi_penanda_dari_form(): void
    {
        $template = $this->storeTemplate();

        $response = $this->actingAs($this->pengurus)->postJson('/letters/preview', [
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '421.1/045.34/PMR/2026',
            'jenis_surat' => 'Keputusan',
            'perihal' => 'Pengangkatan Pradana',
            'isi_surat' => 'Menetapkan Pradana Putra & Pradana Putri.',
            'tujuan_pengirim' => 'UPT SMAN 2 Maros',
            'tgl_surat' => '2026-10-01',
            'template_id' => $template->id,
            'placeholder_values' => $this->placeholderValues(),
        ]);

        $response->assertOk();
        $html = (string) $response->getContent();

        foreach (['421.1/045.34/PMR/2026', 'Ahmad Fauzi', '24567', '0123456789', '198504122010011004', 'KG-09', 'Ambalan Pramuka SMAN 2 Maros'] as $expected) {
            $this->assertStringContainsString($expected, $html, "Penanda tidak muncul di pratinjau: {$expected}");
        }

        $this->assertStringNotContainsString('${', $html, 'Tidak boleh ada penanda kosong di pratinjau.');
    }

    public function test_pratinjau_menandai_penanda_yang_belum_diisi(): void
    {
        $template = $this->storeTemplate();

        $response = $this->actingAs($this->pengurus)->postJson('/letters/preview', [
            'ambalan_id' => $this->ambalan->id,
            'jenis_surat' => 'Keputusan',
            'perihal' => 'Data belum lengkap',
            'tgl_surat' => '2026-10-01',
            'template_id' => $template->id,
            'placeholder_values' => ['kode_kegiatan' => 'KG-09'],
        ]);

        $response->assertOk();

        $html = (string) $response->getContent();

        $this->assertStringContainsString('belum diisi', $html);
        $this->assertStringContainsString('nama_pradana_putra', $html);
        $this->assertStringContainsString('KG-09', $html);
    }

    public function test_pratinjau_tanpa_template_memakai_tata_letak_bawaan(): void
    {
        $this->actingAs($this->pengurus)
            ->postJson('/letters/preview', [
                'ambalan_id' => $this->ambalan->id,
                'jenis_surat' => 'Keluar',
                'perihal' => 'Undanganosidad',
                'isi_surat' => 'Mohon hadir dalam acara.',
                'tujuan_pengirim' => 'UPT SMAN 2 Maros',
                'tgl_surat' => '2026-10-01',
            ])
            ->assertOk()
            ->assertSee('Mohon hadir dalam acara.');
    }

    public function test_surat_tersimpan_lengkap_dengan_nilai_penanda(): void
    {
        $template = $this->storeTemplate();

        $this->actingAs($this->pengurus)
            ->post('/letters', $this->letterPayload($template->id))
            ->assertRedirect(route('letters.index'))
            ->assertSessionHas('success');

        $letter = Letter::firstOrFail();

        $this->assertSame($template->id, $letter->template_id);
        $this->assertSame($this->ambalan->id, $letter->ambalan_id);
        $this->assertSame('Pengangkatan Pradana', $letter->perihal);
        $this->assertSame('Ahmad Fauzi', $letter->placeholder_values['nama_pradana_putra']);
        $this->assertSame('KG-09', $letter->placeholder_values['kode_kegiatan']);
    }

    public function test_ekspor_docx_mengisi_penanda_di_dalam_templat(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());

        $docx = $this->exportDocx($letter);

        $this->assertSame('PK', substr($docx, 0, 2), 'Hasil ekspor harus berupa arsip .docx yang sah.');

        $text = $this->docxText($docx);

        foreach ([
            '421.1/045.34/PMR/2026',
            'Pengangkatan Pradana',
            'Menetapkan Pradana Putra',
            'Ahmad Fauzi',
            '24567',
            '0123456789',
            '198504122010011004',
            'KG-09',
            'Ambalan Pramuka SMAN 2 Maros',
        ] as $expected) {
            $this->assertStringContainsString($expected, $text, "Penanda tidak terisi di .docx: {$expected}");
        }

        $this->assertStringNotContainsString('${', $text, 'Tidak boleh ada penanda kosong di dokumen hasil ekspor.');
    }

    public function test_ekspor_docx_mengisi_penanda_yang_terbelah_antar_run(): void
    {
        $letter = $this->storeLetter($this->storeTemplate($this->splitRunDocument()), [
            'placeholder_values' => array_merge($this->placeholderValues(), ['kode_kegiatan' => 'KG-TERBELAH']),
        ]);

        $text = $this->docxText($this->exportDocx($letter));

        $this->assertStringContainsString('KG-TERBELAH', $text);
        $this->assertStringContainsString('penanda terpecah', $text);
        $this->assertStringNotContainsString('${', $text);
    }

    public function test_ekspor_docx_menulis_baris_baru_sebagai_elemen_breaks(): void
    {
        $letter = $this->storeLetter($this->storeTemplate(), [
            'placeholder_values' => array_merge($this->placeholderValues(), [
                'isi_surat' => "Baris pertama & kedua.\nBaris ketiga <tag> \"kutipan\".",
            ]),
        ]);

        $path = $this->writeTemp($this->exportDocx($letter));
        $documentXml = $this->partXml($path, '#^word/document\.xml$#');
        $text = $this->docxTextFromPath($path);

        $this->assertStringContainsString('Baris pertama & kedua.', $text);
        $this->assertStringContainsString('Baris ketiga <tag> "kutipan".', $text);
        $this->assertStringContainsString(
            '<w:br/>',
            $documentXml,
            'Baris baru harus menjadi elemen w:br di luar w:t, bukan teks di dalam w:t.'
        );
        $this->assertStringNotContainsString('&lt;tag&gt;', $text, 'Karakter < dan > harus tetap terbaca setelah di-escape.');
    }

    public function test_ekspor_docx_mengisi_penanda_kop_dan_footer(): void
    {
        $letter = $this->storeLetter($this->storeTemplate($this->headerFooterDocument()));

        $path = $this->writeTemp($this->exportDocx($letter));

        $this->assertStringContainsString('421.1/045.34/PMR/2026', $this->partText($path, '#^word/header\d+\.xml$#'));
        $this->assertStringContainsString('KG-09', $this->partText($path, '#^word/footer\d+\.xml$#'));
    }

    public function test_ekspor_docx_menghasilkan_arsip_yang_masih_bisa_dibaca(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());
        $path = $this->writeTemp($this->exportDocx($letter));

        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true, 'Arsip .docx hasil ekspor tidak dapat dibuka.');
        $this->assertNotFalse($zip->getFromName('word/document.xml'));

        $document = new \DOMDocument;
        $this->assertTrue(
            $document->loadXML((string) $zip->getFromName('word/document.xml')),
            'XML dokumen hasil ekspor tidak valid, berarti ada tag yang rusak.'
        );

        $zip->close();
    }

    public function test_ekspor_pdf_menghasilkan_berkas_pdf(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());

        $response = $this->actingAs($this->pengurus)->get("/letters/{$letter->id}/generate?format=pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_surat_tanpa_template_tetap_bisa_diekspor(): void
    {
        $letter = $this->storeLetter(null);

        $this->actingAs($this->pengurus)
            ->get("/letters/{$letter->id}/generate?format=docx")
            ->assertOk();

        $text = $this->docxText($this->exportDocx($letter));

        $this->assertStringContainsString('Pengangkatan Pradana', $text);
        $this->assertStringContainsString('Ahmad Fauzi', $text);

        $this->actingAs($this->pengurus)
            ->get("/letters/{$letter->id}/generate?format=pdf")
            ->assertOk();
    }

    public function test_format_ekspor_tidak_dikenal_ditolak(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());

        $this->actingAs($this->pengurus)
            ->get("/letters/{$letter->id}/generate?format=xlsx")
            ->assertRedirect(route('letters.index'))
            ->assertSessionHas('error');
    }

    public function test_anggota_tidak_bisa_membuat_atau_mengekspor_surat(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());
        $anggota = $this->createUser('Anggota');

        $this->actingAs($anggota)->get('/letters')->assertForbidden();
        $this->actingAs($anggota)->post('/letters', $this->letterPayload($letter->template_id))->assertForbidden();
        $this->actingAs($anggota)->get("/letters/{$letter->id}/generate?format=docx")->assertForbidden();
    }

    public function test_halaman_detail_surat_menampilkan_nilai_penanda(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());

        $this->actingAs($this->pengurus)
            ->get("/letters/{$letter->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Letters/Show')
                ->where('letter.id', $letter->id)
                ->where('resolved.nama_pradana_putra', 'Ahmad Fauzi')
                ->where('resolved.kode_kegiatan', 'KG-09')
                ->where('unfilled', [])
            );
    }

    public function test_halaman_cetak_menampilkan_isi_surat(): void
    {
        $letter = $this->storeLetter($this->storeTemplate());

        $this->actingAs($this->pengurus)
            ->get("/letters/{$letter->id}/print")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Letters/Print')
                ->where('letter.id', $letter->id)
                ->has('body')
            )
            ->assertSee('Ahmad Fauzi');
    }

    public function test_template_bisa_dihapus_tanpa_menghapus_surat(): void
    {
        $template = $this->storeTemplate();
        $letter = $this->storeLetter($template);

        $this->actingAs($this->pengurus)
            ->delete("/letters/templates/{$template->id}")
            ->assertRedirect(route('letters.templates'));

        $this->assertSame(0, LetterTemplate::count());
        $this->assertNotNull(Letter::find($letter->id));
    }

    /**
     * @return array<string, string>
     */
    private function placeholderValues(): array
    {
        return [
            'nama_pradana_putra' => 'Ahmad Fauzi',
            'nis_pradana_putra' => '24567',
            'nisn_pradana_putra' => '0123456789',
            'nip_pembina' => '198504122010011004',
            'kode_kegiatan' => 'KG-09',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function letterPayload(?int $templateId, array $overrides = []): array
    {
        return array_merge([
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '421.1/045.34/PMR/2026',
            'jenis_surat' => 'Keputusan',
            'perihal' => 'Pengangkatan Pradana',
            'isi_surat' => 'Menetapkan Pradana Putra dan Pradana Putri.',
            'tujuan_pengirim' => 'UPT SMAN 2 Maros',
            'tgl_surat' => '2026-10-01',
            'waktu_kegiatan' => '07.00 WITA',
            'lokasi_kegiatan' => 'Lapangan UPT SMAN 2 Maros',
            'template_id' => $templateId,
            'placeholder_values' => $this->placeholderValues(),
        ], $overrides);
    }

    private function storeTemplate(?string $path = null): LetterTemplate
    {
        $this->actingAs($this->pengurus)
            ->post('/letters/templates', ['name' => 'Surat Keputusan', 'file' => $this->docxUpload($path ?? $this->templateDocument())])
            ->assertSessionHas('success');

        return LetterTemplate::firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function storeLetter(?LetterTemplate $template, array $overrides = []): Letter
    {
        $this->actingAs($this->pengurus)
            ->post('/letters', $this->letterPayload($template?->id, $overrides))
            ->assertSessionHas('success');

        return Letter::firstOrFail();
    }

    /**
     * Jalankan ekspor .docx lewat HTTP, lalu baca isi berkasnya.
     */
    private function exportDocx(Letter $letter): string
    {
        $response = $this->actingAs($this->pengurus)->get("/letters/{$letter->id}/generate?format=docx");

        $response->assertOk();
        $this->assertStringContainsString('wordprocessingml', (string) $response->headers->get('Content-Type'));

        return (string) file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    private function templateDocument(): string
    {
        $word = new PhpWord;
        $section = $word->addSection();
        $font = ['name' => 'Times New Roman', 'size' => 12];

        $kop = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $kop->setWidth(5000);
        $row = $kop->addRow();
        $row->addCell(6000)->addText('AMBALAN PRAMUKA', [...$font, 'bold' => true], ['align' => 'center']);
        $row->addCell(4000)->addText('Nomor : ${nomor_surat}', $font);

        $section->addText('UPT SMA NEGERI 2 MAROS', [...$font, 'bold' => true], ['align' => 'center']);
        $section->addText('SURAT KEPUTUSAN', $font, ['align' => 'center']);

        $identitas = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $identitas->setWidth(5000);
        foreach ([
            'Nama' => '${nama_pradana_putra}',
            'NIS' => '${nis_pradana_putra}',
            'NISN' => '${nisn_pradana_putra}',
            'Pembina' => '${nip_pembina}',
        ] as $label => $value) {
            $row = $identitas->addRow();
            $row->addCell(2000)->addText($label, $font);
            $row->addCell(7000)->addText(':  '.$value, $font);
        }

        $section->addText('Ambalan : ${nama_ambalan}', $font);
        $section->addText('Kode kegiatan : ${kode_kegiatan}', $font);
        $section->addText('Perihal : ${perihal}', $font);
        $section->addText('Tanggal : ${tgl_surat}', $font);
        $section->addText('Tujuan : ${tujuan_pengirim}', $font);
        $section->addText('${isi_surat}', $font, ['align' => 'both']);
        $section->addText('Ditetapkan di ${nama_ambalan} pada ${tanggal}', $font);

        return $this->saveDocx($word);
    }

    private function plainDocument(): string
    {
        $word = new PhpWord;
        $word->addSection()->addText('Surat tanpa penanda sama sekali.');

        return $this->saveDocx($word);
    }

    /**
     * Word sering memecah satu penanda menjadi beberapa run teks; nilai tetap
     * harus terisi utuh setelah digabung.
     */
    private function splitRunDocument(): string
    {
        $word = new PhpWord;
        $section = $word->addSection();
        $font = ['name' => 'Times New Roman', 'size' => 12];

        foreach (self::PLACEHOLDERS as $name) {
            if ($name !== 'kode_kegiatan') {
                $section->addText('${'.$name.'}', $font);
            }
        }

        $text = $section->addTextRun();
        $text->addText('Kode kegiatan : ${kode');
        $text->addText('_kegiatan}');
        $text->addText(' penanda terpecah.');

        return $this->saveDocx($word);
    }

    private function headerFooterDocument(): string
    {
        $word = new PhpWord;
        $section = $word->addSection();
        $font = ['name' => 'Times New Roman', 'size' => 10];

        foreach (self::PLACEHOLDERS as $name) {
            $section->addText('${'.$name.'}', $font);
        }

        $section->addHeader()->addText('Kop surat nomor ${nomor_surat}', $font);
        $section->addFooter()->addText('Kode kegiatan ${kode_kegiatan}', $font);

        return $this->saveDocx($word);
    }

    private function templateFile(): UploadedFile
    {
        return $this->docxUpload($this->templateDocument());
    }

    private function docxUpload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'template.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );
    }

    private function saveDocx(PhpWord $word): string
    {
        $path = $this->tempPath('docx');
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    private function writeTemp(string $content): string
    {
        $path = $this->tempPath('docx');
        file_put_contents($path, $content);

        return $path;
    }

    private function tempPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'letter_test_');
        unlink($path);

        return $path.'.'.$extension;
    }

    private function createUser(string $role): User
    {
        return User::create([
            'username' => strtolower($role).'_letter_'.uniqid(),
            'name' => ucfirst($role).' Surat',
            'email' => strtolower($role).'.letter.'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Seluruh teks yang terlihat pada body dokumen .docx di dalam memori.
     */
    private function docxText(string $content): string
    {
        return $this->docxTextFromPath($this->writeTemp($content));
    }

    private function docxTextFromPath(string $docxPath): string
    {
        return $this->partText($docxPath, '#^word/document\.xml$#');
    }

    /**
     * Isi mentah satu bagian XML .docx yang polanya cocok.
     */
    private function partXml(string $docxPath, string $partPattern): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true, "Arsip .docx tidak dapat dibuka: {$docxPath}");

        $xml = '';

        for ($index = 0; $index < $zip->numFiles; $index++) {
            if (preg_match($partPattern, (string) $zip->getNameIndex($index))) {
                $xml .= (string) $zip->getFromIndex($index);
            }
        }

        $zip->close();

        $this->assertNotSame('', $xml, "Bagian {$partPattern} tidak ada di dalam .docx.");

        return $xml;
    }

    /**
     * Teks yang terlihat pada bagian XML .docx yang polanya cocok, termasuk
     * pergantian baris dari <w:br/>.
     */
    private function partText(string $docxPath, string $partPattern): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true, "Arsip .docx tidak dapat dibuka: {$docxPath}");

        $text = '';

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);

            if (! preg_match($partPattern, $name)) {
                continue;
            }

            $xml = (string) $zip->getFromIndex($index);

            preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>|<w:br\b[^>]*/?>#s', $xml, $matches);

            foreach ($matches[0] as $position => $match) {
                $text .= str_contains($match, '<w:br')
                    ? "\n"
                    : html_entity_decode($matches[1][$position] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        $zip->close();

        $this->assertNotSame('', $text, "Tidak ada teks pada bagian yang cocok dengan {$partPattern}.");

        return $text;
    }
}
