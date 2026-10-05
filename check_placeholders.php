<?php
require 'vendor/autoload.php';
use App\Support\Letters\DocxTemplate;

$dt = new DocxTemplate();
$placeholders = $dt->placeholders('file dokumen/template-surat-keputusan.docx');
echo 'Placeholders: ' . implode(', ', $placeholders) . PHP_EOL;