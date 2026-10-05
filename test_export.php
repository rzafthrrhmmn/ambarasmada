<?php
require 'vendor/autoload.php';
use App\Support\Letters\DocxTemplate;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\Ambalan;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Storage::fake('public');

// Create template
$templateDoc = (new \PhpOffice\PhpWord\PhpWord());
$section = $templateDoc->addSection();
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

$writer = \PhpOffice\PhpWord\IOFactory::createWriter($templateDoc, 'Word2007');
$templatePath = sys_get_temp_dir() . '/test_template_' . uniqid() . '.docx';
$writer->save($templatePath);

// Upload template
$template = LetterTemplate::create([
    'name' => 'Test Template',
    'file_path' => 'letter-templates/test.docx',
    'placeholders' => ['nomor_surat', 'perihal', 'isi_surat', 'tujuan_pengirim', 'tgl_surat', 'nama_ambalan', 'tanggal', 'nama_pradana_putra', 'nis_pradana_putra', 'nisn_pradana_putra', 'nip_pembina', 'kode_kegiatan'],
    'created_by_user_id' => 1,
]);

// Copy template to storage
Storage::disk('public')->put('letter-templates/test.docx', file_get_contents($templatePath));

// Create letter
$ambalan = Ambalan::firstOrCreate(['kode' => 'AMB', 'nama' => 'Ambalan Test']);
$letter = Letter::create([
    'ambalan_id' => $ambalan->id,
    'nomor_surat' => 'TEST-001',
    'jenis_surat' => 'Keputusan',
    'perihal' => 'Test Perihal',
    'isi_surat' => 'Test isi surat.',
    'tujuan_pengirim' => 'Test Tujuan',
    'tgl_surat' => '2026-01-01',
    'template_id' => $template->id,
    'placeholder_values' => [
        'nama_pradana_putra' => 'Test Putra',
        'nis_pradana_putra' => '12345',
        'nisn_pradana_putra' => '1234567890',
        'nip_pembina' => '198001012010011001',
        'kode_kegiatan' => 'KG-01',
    ],
    'created_by_user_id' => 1,
]);

// Export
$controller = app(\App\Http\Controllers\LetterController::class);
$response = $controller->generate(new \Illuminate\Http\Request(['format' => 'docx']), $letter);

// Save response
$outputPath = sys_get_temp_dir() . '/exported_' . uniqid() . '.docx';
file_put_contents($outputPath, $response->getContent());

// Check output
$zip = new ZipArchive();
$zip->open($outputPath);
$xml = $zip->getFromName('word/document.xml');
$zip->close();

echo "Tables in export: " . substr_count($xml, '<w:tbl') . "\n";

if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $xml, $m)) {
    echo "Body length: " . strlen($m[1]) . "\n";
}

@unlink($templatePath);
@unlink($outputPath);