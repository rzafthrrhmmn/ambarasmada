<?php
require 'vendor/autoload.php';
use PhpOffice\PhpWord\IOFactory;

$doc = IOFactory::load('file dokumen/template-surat-keputusan.docx');
echo 'Sections count: ' . count($doc->getSections()) . PHP_EOL;
foreach ($doc->getSections() as $i => $section) {
    echo 'Section ' . $i . ': ' . count($section->getElements()) . ' elements' . PHP_EOL;
    foreach ($section->getElements() as $el) {
        echo '  - ' . get_class($el) . PHP_EOL;
    }
}
$sections = $doc->getSections();
if (count($sections) > 0) {
    $header = $sections[0]->getHeader();
    if ($header) {
        echo 'Header elements: ' . count($header->getElements()) . PHP_EOL;
        foreach ($header->getElements() as $el) {
            echo '  H: ' . get_class($el) . PHP_EOL;
        }
    }
    $footer = $sections[0]->getFooter();
    if ($footer) {
        echo 'Footer elements: ' . count($footer->getElements()) . PHP_EOL;
        foreach ($footer->getElements() as $el) {
            echo '  F: ' . get_class($el) . PHP_EOL;
        }
    }
}