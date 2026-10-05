<?php
require 'vendor/autoload.php';
use App\Support\Letters\DocxTemplate;

$dt = new DocxTemplate();
$values = [
    'nomor_surat' => 'TEST-001',
    'email_ambalan' => 'test@example.com',
    'perihal' => 'Test Perihal',
    'nama_pradana_putra' => 'Test Putra',
    'nis_pradana_putra' => '12345',
    'nisn_pradana_putra' => '1234567890',
    'nama_pradana_putri' => 'Test Putri',
    'nis_pradana_putri' => '54321',
    'nisn_pradana_putri' => '0987654321',
    'nama_pembina' => 'Test Pembina',
    'nip_pembina' => '198001012010011001',
    'isi_surat' => 'Test isi surat.',
    'tanggal' => '1 Januari 2026',
    'nip_pengirim' => '197001012010011002',
];

$outputPath = sys_get_temp_dir() . '/test_fill_' . uniqid() . '.docx';
$result = $dt->fill('file dokumen/template-surat-keputusan.docx', $values, $outputPath);

echo "Filled: " . implode(', ', $result['filled']) . "\n";
echo "Missing: " . implode(', ', $result['missing']) . "\n";

// Check output
$zip = new ZipArchive();
$zip->open($outputPath);
$xml = $zip->getFromName('word/document.xml');
$zip->close();

echo "Tables in output: " . substr_count($xml, '<w:tbl') . "\n";

if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $xml, $m)) {
    echo "Body length: " . strlen($m[1]) . "\n";
    echo "First 500: " . substr($m[1], 0, 500) . "\n";
}

@unlink($outputPath);