<?php
require 'vendor/autoload.php';
use PhpOffice\PhpWord\IOFactory;

$doc = IOFactory::load('file dokumen/template-surat-keputusan.docx');
echo 'Sections count: ' . count($doc->getSections()) . PHP_EOL;
foreach ($doc->getSections() as $i => $section) {
    echo 'Section ' . $i . ': ' . count($section->getElements()) . ' elements' . PHP_EOL;
    $sectPr = $section->getStyle();
    if ($sectPr) {
        echo '  Page size: ' . ($sectPr->getPageSizeW() ?? 'N/A') . 'x' . ($sectPr->getPageSizeH() ?? 'N/A') . PHP_EOL;
        echo '  Margins: T=' . ($sectPr->getMarginTop() ?? 'N/A') . ' R=' . ($sectPr->getMarginRight() ?? 'N/A') . ' B=' . ($sectPr->getMarginBottom() ?? 'N/A') . ' L=' . ($sectPr->getMarginLeft() ?? 'N/A') . PHP_EOL;
    }
}