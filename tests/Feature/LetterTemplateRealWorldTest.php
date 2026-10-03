<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use App\Support\Letters\LetterValues;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

/**
 * Uji coba alur persuratan memakai berkas .docx yang benar-benar dipakai
 * sekolah, lalu menyimpan hasil ekspornya ke folder "file dokumen" supaya
 * bisa dibuka di Word dan diperiksa dengan mata.
 *
 * Pengujian ini hanya berjalan bila PERSURATAN_TEMPLATE_REAL diaktifkan,
 * karena butuh berkas contoh dari luar repositori:
 *
 *   PERSURATAN_TEMPLATE_REAL="file dokumen/Undangan_Template.docx" \
 *   php artisan test --filter=LetterTemplateRealWorldTest
 */
class LetterTemplateRealWorldTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    private Ambalan $ambalan;

    private string $outputDir;

    private string $source = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = (string) env('PERSURATAN_TEMPLATE_REAL');

        if ($this->source === '' || ! is_file($this->source)) {
            $this->markTestSkipped('Isi PERSURATAN_TEMPLATE_REAL dengan berkas .docx untuk menjalankan uji ini.');
        }

        Storage::fake('public');

        $this->outputDir = base_path('file dokumen/hasil-uji');
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        $this->ambalan = Ambalan::create(['kode' => 'AMB', 'nama' => 'Ambalan Pramuka UPT SMAN 2 Maros']);
        $this->pengurus = User::create([
            'username' => 'pengurus_real_'.uniqid(),
            'name' => 'Pengurus Uji',
            'email' => 'uji.persuratan@test.com',
            'password' => bcrypt('password'),
            'role' => 'Pengurus',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }

    public function test_template_asli_dapat_diunggah_diisi_dan_diekspor(): void
    {
        $this->actingAs($this->pengurus)->post('/letters/templates', [
            'name' => 'Template Asli Sekolah',
            'file' => new UploadedFile(
                $this->source,
                'template.docx',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                null,
                true
            ),
        ])->assertSessionHas('success');

        $template = LetterTemplate::firstOrFail();
        $placeholders = $template->placeholderList();

        $this->assertNotEmpty($placeholders, 'Template asli harus punya penanda.');

        $this->actingAs($this->pengurus)->post('/letters', [
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '421.1/045.34/PMR/2026',
            'jenis_surat' => 'Keluar',
            'perihal' => 'Undangan Rapat Pembina & Evaluasi 100% (A+)',
            'tujuan_pengirim' => 'Ketua Gugun Depan Ambalan',
            'tgl_surat' => '2026-10-01',
            'waktu_kegiatan' => '07.00 s.d. 12.00 WITA',
            'lokasi_kegiatan' => 'Lapangan UPT SMA Negeri 2 Maros',
            'isi_surat' => "Sehubungan dengan Akan dilaksanakan 30 baris.\nBaris kedua & ketiga <tag> \"kutipan\".",
            'template_id' => $template->id,
            'placeholder_values' => $this->placeholderValues(),
        ])->assertSessionHas('success');

        $letter = Letter::firstOrFail();
        $docx = $this->export($letter, 'docx');
        $pdf = $this->export($letter, 'pdf');

        $this->artifact('01-template-asli.docx', (string) file_get_contents($this->source));
        $this->artifact('02-surat-hasil.docx', $docx);
        $this->artifact('03-surat-hasil.pdf', $pdf);

        // Pratinjau memakai HTML yang sama persis dengan sumber PDF, jadi
        // berkas ini bisa dibuka di peramban untuk memeriksa tata letak.
        $this->artifact(
            '06-pratinjau.html',
            (string) $this->actingAs($this->pengurus)->postJson('/letters/preview', [
                'ambalan_id' => $this->ambalan->id,
                'nomor_surat' => '421.1/045.34/PMR/2026',
                'jenis_surat' => 'Keluar',
                'perihal' => 'Undangan Rapat Pembina & Evaluasi 100% (A+)',
                'tujuan_pengirim' => 'Ketua Gugun Depan Ambalan',
                'tgl_surat' => '2026-10-01',
                'waktu_kegiatan' => '07.00 s.d. 12.00 WITA',
                'lokasi_kegiatan' => 'Lapangan UPT SMA Negeri 2 Maros',
                'isi_surat' => "Sehubungan dengan Akan dilaksanakan 30 baris.\nBaris kedua & ketiga <tag> \"kutipan\".",
                'template_id' => $template->id,
                'placeholder_values' => $this->placeholderValues(),
            ])->assertOk()->getContent()
        );

        $text = $this->docxText($docx);
        $isiSurat = "Sehubungan dengan Akan dilaksanakan 30 baris.\nBaris kedua & ketiga <tag> \"kutipan\".";
        $values = $this->placeholderValues() + [
            'nomor_surat' => '421.1/045.34/PMR/2026',
            'perihal' => 'Undangan Rapat Pembina & Evaluasi 100% (A+)',
            'tujuan_pengirim' => 'Ketua Gugun Depan Ambalan',
            'tgl_surat' => '1 Oktober 2026',
            'waktu_kegiatan' => '07.00 s.d. 12.00 WITA',
            'lokasi_kegiatan' => 'Lapangan UPT SMA Negeri 2 Maros',
            'nama_ambalan' => 'Ambalan Pramuka UPT SMAN 2 Maros',
            'isi_surat' => $isiSurat,
            'tanggal' => (new LetterValues)->base($letter)['tanggal'],
        ];

        // Hanya nilai untuk penanda yang benar-benar dipakai template ini yang
        // boleh diperiksa; sisanya memang tidak ada di dokumen.
        foreach ($placeholders as $name) {
            $this->assertArrayHasKey($name, $values, "Template punya penanda {$name} tanpa nilai di form.");
            $this->assertStringContainsString((string) $values[$name], $text, "Nilai tidak masuk ke .docx: {$name}");
        }

        $this->assertStringContainsString('1 Oktober 2026', $text, 'Tanggal surat harus ditulis dalam bahasa Indonesia.');
        $this->assertStringContainsString('Baris kedua & ketiga <tag> "kutipan".', $text);
        $this->assertStringNotContainsString('${', $text, 'Masih ada penanda kosong di .docx hasil ekspor.');
        $this->assertStringStartsWith('%PDF', $pdf);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->outputDir.'/02-surat-hasil.docx') === true);

        $document = new DOMDocument;
        $this->assertTrue(
            $document->loadXML((string) $zip->getFromName('word/document.xml')),
            'XML .docx hasil ekspor tidak valid; buka di Word akan complains.'
        );
        $zip->close();

        fwrite(STDERR, "\n Penanda terbaca : ".implode(', ', $placeholders)."\n");
        fwrite(STDERR, ' Berkas hasil     : '.realpath($this->outputDir)."\n");
        fwrite(STDERR, ' DOCX            : '.number_format(strlen($docx))." byte\n");
        fwrite(STDERR, ' PDF             : '.number_format(strlen($pdf))." byte\n\n");
    }

    public function test_surat_tanpa_template_asli_tetap_diekspor(): void
    {
        $this->actingAs($this->pengurus)->post('/letters', [
            'ambalan_id' => $this->ambalan->id,
            'nomor_surat' => '800/SK/AMB/2026',
            'jenis_surat' => 'Keputusan',
            'perihal' => 'Pengangkatan Pradana',
            'tujuan_pengirim' => 'UPT SMAN 2 Maros',
            'tgl_surat' => '2026-10-01',
            'isi_surat' => 'Menetapkan Pradana Putra dan Pradana Putri untuk periode 2026/2027.',
            'placeholder_values' => $this->placeholderValues(),
        ])->assertSessionHas('success');

        $letter = Letter::firstOrFail();

        $this->artifact('04-tanpa-template.docx', $this->export($letter, 'docx'));
        $this->artifact('05-tanpa-template.pdf', $this->export($letter, 'pdf'));

        $text = $this->docxText((string) file_get_contents($this->outputDir.'/04-tanpa-template.docx'));

        $this->assertStringContainsString('Pengangkatan Pradana', $text);
        $this->assertStringContainsString('Ahmad Fauzi', $text);
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
            'nama_pradana_putri' => 'Siti Aminah',
            'nis_pradana_putri' => '24568',
            'nisn_pradana_putri' => '0123456790',
            'nama_pembina' => 'Budi Santoso, S.Pd.',
            'nip_pembina' => '198504122010011004',
            'nip_pengirim' => '199001152015032002',
            'email_ambalan' => 'ambalan@sman2maros.sch.id',
            'kode_kegiatan' => 'KG-09',
        ];
    }

    private function export(Letter $letter, string $format): string
    {
        $response = $this->actingAs($this->pengurus)->get("/letters/{$letter->id}/generate?format={$format}");

        $response->assertOk();

        if ($response->baseResponse instanceof BinaryFileResponse) {
            return (string) file_get_contents($response->baseResponse->getFile()->getPathname());
        }

        return (string) $response->getContent();
    }

    /**
     * Tulis berkas hasil uji. Berkas lama dihapus dulu karena Windows menahan
     * berkas yang baru saja dibaca arsip .docx.
     */
    private function artifact(string $name, string $content): void
    {
        $path = $this->outputDir.'/'.$name;

        if (is_file($path)) {
            unlink($path);
        }

        file_put_contents($path, $content);
    }

    private function docxText(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'real_').'.docx';
        file_put_contents($path, $content);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>|<w:br\b[^>]*/?>#s', $xml, $matches);

        $text = '';

        foreach ($matches[0] as $index => $match) {
            $text .= str_contains($match, '<w:br')
                ? "\n"
                : html_entity_decode($matches[1][$index] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        return $text;
    }
}
