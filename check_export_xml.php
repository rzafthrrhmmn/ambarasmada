<?php
require 'vendor/autoload.php';

$zip = new ZipArchive();
$zip->open('file dokumen/hasil-uji/02-surat-hasil.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

// Count tables
echo "Tables in export: " . substr_count($xml, '<w:tbl') . "\n";

// Check for table structure
if (preg_match_all('#<w:tbl\b.*?</w:tbl>#s', $xml, $matches)) {
    echo "Found " . count($matches[0]) . " table blocks\n";
    foreach ($matches[0] as $i => $tbl) {
        echo "Table $i: " . strlen($tbl) . " chars\n";
    }
} else {
    echo "No complete table tags found\n";
}

// Show first 1000 chars of body
if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $xml, $m)) {
    echo "\n--- Export body (first 1000) ---\n";
    echo substr($m[1], 0, 1000) . "\n";
}

// Check if placeholders were replaced
foreach (['nomor_surat', 'perihal', 'isi_surat', 'tanggal'] as $ph) {
    if (strpos($xml, '${'.$ph.'}') !== false) {
        echo "WARNING: Placeholder \${$ph} NOT replaced!\n";
    } else {
        echo "OK: \${$ph} replaced\n";
    }
}