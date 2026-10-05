<?php
require 'vendor/autoload.php';

$zip = new ZipArchive();
$zip->open('file dokumen/template-surat-keputusan.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

// Find nomor_surat
$pos = strpos($xml, 'nomor_surat');
if ($pos !== false) {
    echo "Found at position $pos\n";
    // Show context
    $start = max(0, $pos - 200);
    $end = min(strlen($xml), $pos + 200);
    echo substr($xml, $start, $end - $start) . "\n\n";
}

// Find all placeholders
preg_match_all('/\$\{([^}]+)\}/', $xml, $matches);
foreach ($matches[0] as $i => $m) {
    $pos = strpos($xml, $m);
    echo "Placeholder: $m at pos $pos\n";
    if ($pos !== false) {
        $start = max(0, $pos - 100);
        $end = min(strlen($xml), $pos + 100);
        echo "  Context: " . substr($xml, $start, $end - $start) . "\n\n";
    }
}