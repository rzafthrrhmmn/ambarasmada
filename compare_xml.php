<?php
require 'vendor/autoload.php';

function extractDocumentXml($path) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        die("Cannot open $path");
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    return $xml;
}

$templateXml = extractDocumentXml('file dokumen/template-surat-keputusan.docx');
$exportXml = extractDocumentXml('file dokumen/hasil-uji/02-surat-hasil.docx');

// Count tables
$templateTables = substr_count($templateXml, '<w:tbl');
$exportTables = substr_count($exportXml, '<w:tbl');
echo "Template tables: $templateTables\n";
echo "Export tables: $exportTables\n";

// Check for sectPr
if (preg_match('#<w:sectPr\b.*?</w:sectPr>#s', $templateXml, $m)) {
    echo "Template sectPr: " . strlen($m[0]) . " chars\n";
}
if (preg_match('#<w:sectPr\b.*?</w:sectPr>#s', $exportXml, $m)) {
    echo "Export sectPr: " . strlen($m[0]) . " chars\n";
}

// Check body content length
if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $templateXml, $m)) {
    echo "Template body: " . strlen($m[1]) . " chars\n";
}
if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $exportXml, $m)) {
    echo "Export body: " . strlen($m[1]) . " chars\n";
}

// Show first 500 chars of body
if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $templateXml, $m)) {
    echo "\n--- Template body (first 500) ---\n";
    echo substr($m[1], 0, 500) . "\n";
}
if (preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $exportXml, $m)) {
    echo "\n--- Export body (first 500) ---\n";
    echo substr($m[1], 0, 500) . "\n";
}