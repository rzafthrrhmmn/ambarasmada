<?php

namespace App\Support\Letters;

use RuntimeException;
use ZipArchive;

/**
 * Pembaca, pengisi, dan perender templat surat (.docx).
 *
 * Templat surat ditulis pengguna di Word dan diisi penanda ${nama_field}.
 * Penanda boleh utuh di dalam satu run teks atau terpotong beberapa run
 * (Word sering memecah `${nomor_surat}` menjadi `${nomor` + `_surat}`),
 * sehingga penanda dicari pada aliran teks gabungan seluruh run `<w:t>`,
 * lalu nilainya ditulis kembali ke run pertama penanda tersebut sehingga
 * format huruf penanda tetap terjaga.
 *
 * Semua bagian paket docx ikut diproses: body, header, dan footer, karena
 * kop surat sering memuat nomor dan tanggal surat.
 */
class DocxTemplate
{
    /** Bentuk penanda yang dikenali, contoh: ${nama_pradana_putra}. */
    public const PLACEHOLDER_PATTERN = '/\$\{\s*([A-Za-z0-9_]+)\s*\}/';

    private const BODY_PART = 'word/document.xml';

    private const XML_PARTS = '#^word/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$#';

    /**
     * Daftar penanda yang dipakai templat pada file .docx tertentu.
     *
     * @return array<int, string>
     */
    public function placeholders(string $path): array
    {
        $found = [];

        foreach ($this->parts($path) as $xml) {
            foreach ($this->scan($xml) as $name) {
                $found[$name] = true;
            }
        }

        return array_keys($found);
    }

    /**
     * Isi penanda pada templat lalu tulis berkas .docx hasil ke $destination.
     *
     * @param  array<string, string|null>  $values
     * @return array{path: string, filled: array<int, string>, missing: array<int, string>}
     */
    public function fill(string $path, array $values, string $destination): array
    {
        $source = $this->open($path);
        $result = $this->openForWrite($destination);

        $filled = [];
        $missing = [];

        try {
            for ($index = 0; $index < $source->numFiles; $index++) {
                $name = $source->getNameIndex($index);
                $content = $source->getFromIndex($index);

                if ($content === false) {
                    $result->addFromString($name, '');

                    continue;
                }

                if ($this->isXmlPart($name)) {
                    [$content, $partFilled, $partMissing] = $this->applyValues($content, $values);
                    $filled = array_merge($filled, $partFilled);
                    $missing = array_merge($missing, $partMissing);
                }

                $result->addFromString($name, $content);
            }
        } finally {
            $result->close();
            $source->close();
        }

        return [
            'path' => $destination,
            'filled' => array_values(array_unique($filled)),
            'missing' => array_values(array_unique($missing)),
        ];
    }

    /**
     * Ubah isi templat menjadi potongan HTML untuk pratinjau dan ekspor PDF.
     *
     * Hasilnya mengikuti urutan paragraf, perataan, tebal, miring, dan ukuran
     * huruf dari dokumen aslinya. Berkas gambar tidak disalin, sehingga hasil
     * pratinjur adalah isi teks, bukan replika visual dokumen.
     */
    public function toHtml(string $path, array $values): string
    {
        [$xml] = $this->applyValues($this->bodyXml($path), $values);

        return $this->xmlToHtml($xml);
    }

    /**
     * Ganti penanda pada satu bagian XML.
     *
     * @param  array<string, string|null>  $values
     * @return array{0: string, 1: array<int, string>, 2: array<int, string>}
     */
    public function applyValues(string $xml, array $values): array
    {
        $runs = $this->textRuns($xml);

        if ($runs === []) {
            return [$xml, [], []];
        }

        $plain = '';

        foreach ($runs as $index => $run) {
            $runs[$index]['offset'] = strlen($plain);
            $plain .= $run['text'];
        }

        if (! preg_match_all(static::PLACEHOLDER_PATTERN, $plain, $matches, PREG_OFFSET_CAPTURE)) {
            return [$xml, [], []];
        }

        $edits = [];
        $filled = [];
        $missing = [];

        foreach ($matches[0] as $index => $match) {
            $name = $matches[1][$index][0];
            $start = $match[1];
            $end = $start + strlen($match[0]);

            if (array_key_exists($name, $values) && $values[$name] !== null) {
                $filled[] = $name;
            } else {
                $missing[] = $name;
            }

            $this->planRunEdits($runs, $start, $end, (string) ($values[$name] ?? ''), $edits);
        }

        return [$this->commitRunEdits($xml, $edits), $filled, $missing];
    }

    /**
     * XML body dokumen, dipakai perender HTML.
     */
    public function bodyXml(string $path): string
    {
        $parts = $this->parts($path);
        $xml = $parts[static::BODY_PART] ?? reset($parts);

        if (! is_string($xml)) {
            throw new RuntimeException('Berkas .docx tidak memiliki word/document.xml.');
        }

        return $xml;
    }

    /**
     * Apakah berkas bisa dipakai sebagai templat: arsip docx yang punya body
     * dan minimal satu penanda.
     */
    public function isValidTemplate(string $path): bool
    {
        try {
            $parts = $this->parts($path);

            return isset($parts[static::BODY_PART]) && $this->scan($parts[static::BODY_PART]) !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Seluruh bagian XML yang bisa memuat teks: body, header, dan footer.
     *
     * @return array<string, string>
     */
    protected function parts(string $path): array
    {
        $zip = $this->open($path);
        $parts = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);

                if (! $this->isXmlPart($name)) {
                    continue;
                }

                $content = $zip->getFromIndex($index);

                if ($content !== false) {
                    $parts[$name] = $content;
                }
            }
        } finally {
            $zip->close();
        }

        if ($parts === []) {
            throw new RuntimeException('Berkas .docx tidak dapat dibaca.');
        }

        return $parts;
    }

    protected function isXmlPart(string $name): bool
    {
        return (bool) preg_match(static::XML_PARTS, $name);
    }

    protected function open(string $path): ZipArchive
    {
        if (! is_file($path)) {
            throw new RuntimeException("Berkas templat tidak ditemukan: {$path}");
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::RDONLY);

        if ($opened !== true) {
            throw new RuntimeException("Berkas .docx rusak atau bukan arsip zip: {$path}");
        }

        return $zip;
    }

    protected function openForWrite(string $destination): ZipArchive
    {
        $directory = dirname($destination);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Folder tujuan tidak dapat dibuat: {$directory}");
        }

        $zip = new ZipArchive;
        $opened = $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new RuntimeException("Gagal menulis berkas .docx: {$destination}");
        }

        return $zip;
    }

    /**
     * @return array<int, string>
     */
    protected function scan(string $xml): array
    {
        $plain = '';

        foreach ($this->textRuns($xml) as $run) {
            $plain .= $run['text'];
        }

        if (! preg_match_all(static::PLACEHOLDER_PATTERN, $plain, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }

    /**
     * Potongan <w:t> beserta posisi offset byte di dalam XML.
     *
     * `inner` adalah awal teks di dalam tag, `textEnd` adalah ujung teks, jadi
     * rentang [inner, textEnd) adalah teks yang boleh ditimpa tanpa merusak
     * tag pembuka maupun tag penutup.
     *
     * @return array<int, array{index: int, start: int, end: int, inner: int, textEnd: int, text: string}>
     */
    protected function textRuns(string $xml): array
    {
        $runs = [];

        if (! preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>|<w:t(?:\s[^>]*)?/>#s', $xml, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        foreach ($matches[0] as $index => $whole) {
            $start = $whole[1];
            $text = $matches[1][$index][1] === -1 ? '' : $matches[1][$index][0];
            $openLength = str_contains($whole[0], '</w:t>') ? (int) strpos($whole[0], '>') + 1 : 0;
            $inner = $start + $openLength;

            $runs[] = [
                'index' => $index,
                'start' => $start,
                'end' => $start + strlen($whole[0]),
                'inner' => $inner,
                'textEnd' => $inner + strlen($text),
                'text' => $text,
            ];
        }

        return $runs;
    }

    /**
     * Susun rencana ubah teks per run untuk satu rentang penanda.
     *
     * Nilai ditulis penuh ke run pertama penanda, sisa run penanda dikosongkan,
     * sehingga format(run pertama) tetap dipakai untuk nilai akhir.
     *
     * @param  array<int, array{index: int, start: int, end: int, inner: int, textEnd: int, text: string, offset: int}>  $runs
     * @param  array<int, array{run: int, offset: int, length: int, value: string}>  $edits
     */
    protected function planRunEdits(array $runs, int $start, int $end, string $value, array &$edits): void
    {
        $first = $this->runAt($runs, $start);
        $last = $this->runAt($runs, $end - 1);

        if ($first === null || $last === null || $last['index'] < $first['index']) {
            return;
        }

        $rendered = $this->renderValue($value);

        for ($index = $first['index']; $index <= $last['index']; $index++) {
            $run = $runs[$index];
            $runOffset = $index === $first['index'] ? $start - $run['offset'] : 0;
            $runEnd = $index === $last['index'] ? $end - $run['offset'] : strlen($run['text']);
            $runEnd = max($runEnd, $runOffset);

            $edits[] = [
                'run' => $index,
                'offset' => $runOffset,
                'length' => $runEnd - $runOffset,
                'value' => $index === $first['index'] ? $rendered : '',
            ];
        }
    }

    /**
     * Terapkan rencana ubah teks ke XML.
     *
     * Run diproses dari belakang ke depan supaya panjang run yang berubah tidak
     * menggeser offset run yang belum diproses; di dalam satu run, ubahan juga
     * diterapkan dari offset terbesar.
     *
     * @param  array<int, array{run: int, offset: int, length: int, value: string}>  $edits
     */
    protected function commitRunEdits(string $xml, array $edits): string
    {
        if ($edits === []) {
            return $xml;
        }

        $runs = $this->textRuns($xml);
        $grouped = [];

        foreach ($edits as $edit) {
            $grouped[$edit['run']][] = $edit;
        }

        krsort($grouped);

        foreach ($grouped as $runIndex => $runEdits) {
            if (! isset($runs[$runIndex])) {
                continue;
            }

            $run = $runs[$runIndex];
            $text = $run['text'];

            usort($runEdits, fn (array $a, array $b) => $b['offset'] <=> $a['offset']);

            foreach ($runEdits as $edit) {
                $text = substr($text, 0, $edit['offset'])
                    .$edit['value']
                    .substr($text, $edit['offset'] + $edit['length']);
            }

            $xml = substr($xml, 0, $run['inner']).$text.substr($xml, $run['textEnd']);
        }

        return $xml;
    }

    /**
     * @param  array<int, array{index: int, start: int, end: int, inner: int, textEnd: int, text: string, offset: int}>  $runs
     * @return array{index: int, start: int, end: int, inner: int, textEnd: int, text: string, offset: int}|null
     */
    protected function runAt(array $runs, int $plainOffset): ?array
    {
        foreach ($runs as $run) {
            if ($plainOffset >= $run['offset'] && $plainOffset < $run['offset'] + strlen($run['text'])) {
                return $run;
            }
        }

        return null;
    }

    /**
     * Nilai dari form diubah ke XML Word: karakter di-escape dan baris baru
     * menjadi elemen <w:br/>. Karena <w:br/> tidak boleh berada di dalam
     * <w:t>, run teks dipecah dan dibuka ulang di setiap pergantian baris
     * sehingga format huruf run asalnya tetap terbawa.
     */
    protected function renderValue(string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return str_replace(["\r\n", "\r", "\n"], '</w:t><w:br/><w:t xml:space="preserve">', $escaped);
    }

    /**
     * Ambil isi <w:body> dan buang bagian yang tidak menghasilkan teks
     * ( pengaturan halaman, daftar isi, dan memoisasi field Word ).
     */
    protected function bodyContent(string $xml): string
    {
        if (preg_match('#<w:body(?:\s[^>]*)?>(.*)</w:body>#s', $xml, $match)) {
            $xml = $match[1];
        }

        foreach (['#<w:sectPr\b.*?</w:sectPr>#s', '#<w:sdt>.*?</w:sdt>#s', '#<w:bookmarkStart\b[^>]*/>#s', '#<w:bookmarkEnd\b[^>]*/>#s'] as $pattern) {
            $xml = preg_replace($pattern, '', $xml) ?? $xml;
        }

        return $xml;
    }

    protected function xmlToHtml(string $xml): string
    {
        $xml = $this->bodyContent($xml);
        $tables = [];

        $xml = preg_replace_callback('#<w:tbl(?:\s[^>]*)?>.*?</w:tbl>#s', function (array $match) use (&$tables) {
            if (substr_count($match[0], '<w:tbl>') + substr_count($match[0], '<w:tbl ') > 1) {
                return $match[0];
            }

            $token = '<!--table:'.count($tables).'-->';
            $tables[] = $this->tableToHtml($match[0]);

            return $token;
        }, $xml) ?? $xml;

        $html = preg_replace_callback('#<w:p(?:\s[^>]*)?(?:/>|>.*?</w:p>)#s', function (array $match) {
            return $this->paragraphToHtml($match[0]);
        }, $xml) ?? $xml;

        foreach ($tables as $index => $table) {
            $html = str_replace('<!--table:'.$index.'-->', $table, $html);
        }

        // Sisa tag Word (properti paragraf, sel, tabel) dibuang karena isinya
        // sudah diterjemahkan ke gaya HTML di atas.
        $html = preg_replace('#</?w:[A-Za-z]+\b[^>]*/?>#', '', $html) ?? $html;

        return trim($html);
    }

    protected function paragraphToHtml(string $paragraph): string
    {
        $text = $this->runsToText($paragraph);

        if (trim(strip_tags($text)) === '') {
            return '<p class="kosong">&nbsp;</p>';
        }

        $style = [];

        if (preg_match('#<w:jc\s[^>]*w:val="([a-zA-Z]+)"#', $paragraph, $match)) {
            $style[] = 'text-align:'.match (strtolower($match[1])) {
                'center' => 'center',
                'right', 'end' => 'right',
                'both', 'distribute' => 'justify',
                default => 'left',
            };
        }

        if (preg_match('#<w:sz\s[^>]*w:val="(\d+)"#', $paragraph, $match)) {
            $style[] = 'font-size:'.max(6, (int) round(((int) $match[1]) / 2)).'pt';
        }

        if (preg_match('#<w:ind\s[^>]*w:left="(\d+)"#', $paragraph, $match)) {
            $style[] = 'margin-left:'.max(0, (int) round(((int) $match[1]) / 20)).'pt';
        }

        $property = $this->paragraphProperty($paragraph);

        if ($this->runProperty($paragraph, 'b') || $this->runProperty($property, 'b')) {
            $style[] = 'font-weight:bold';
        }

        if ($this->runProperty($paragraph, 'i') || $this->runProperty($property, 'i')) {
            $style[] = 'font-style:italic';
        }

        if ($this->runProperty($paragraph, 'u')) {
            $style[] = 'text-decoration:underline';
        }

        $attributes = $style === [] ? '' : ' style="'.implode(';', $style).'"';

        return '<p'.$attributes.'>'.$text.'</p>';
    }

    protected function tableToHtml(string $table): string
    {
        if (! preg_match_all('#<w:tr(?:\s[^>]*)?>.*?</w:tr>#s', $table, $rows)) {
            return '<p class="kosong">&nbsp;</p>';
        }

        $class = $this->tableHasBorder($table) ? 'tabel-bergaris' : 'tabel-polos';
        $html = '<table class="'.$class.'">';

        foreach ($rows[0] as $row) {
            $html .= '<tr>';

            if (preg_match_all('#<w:tc(?:\s[^>]*)?>.*?</w:tc>#s', $row, $cells)) {
                foreach ($cells[0] as $cell) {
                    $width = $this->cellWidth($cell);
                    $attribute = $width === null ? '' : ' style="width:'.$width.'"';
                    $html .= '<td'.$attribute.'>'.$this->xmlToHtml($cell).'</td>';
                }
            }

            $html .= '</tr>';
        }

        return $html.'</table>';
    }

    /**
     * Lebar sel dalam satuan dxa (1/20 pt) diubah ke persen lebar halaman.
     */
    protected function cellWidth(string $cell): ?string
    {
        if (! preg_match('#<w:tcW\s[^>]*w:w="(\d+)"[^>]*w:type="dxa"#', $cell, $match)
            && ! preg_match('#<w:tcW\s[^>]*w:type="dxa"[^>]*w:w="(\d+)"#', $cell, $match)) {
            return null;
        }

        $twips = (int) $match[1];
        $pageWidth = 11906 - 1418 - 1134;

        return max(4, min(100, (int) round($twips / $pageWidth * 100))).'%';
    }

    /**
     * Garis tabel disimpulkan dari <w:tblBorders>. Template yang ditulis dengan
     * borderSize 0 menghasilkan garis berukuran 0 dan dianggap polos.
     */
    protected function tableHasBorder(string $table): bool
    {
        if (! preg_match('#<w:tblBorders>(.*?)</w:tblBorders>#s', $table, $match)) {
            return false;
        }

        return (bool) preg_match('#w:val="(?:single|double|dashed|dotted|thick)"[^>]*w:sz="([1-9]\d*)"#', $match[1])
            || (bool) preg_match('#w:sz="([1-9]\d*)"[^>]*w:val="(?:single|double|dashed|dotted|thick)"#', $match[1]);
    }

    /**
     * Teks terlihat dari satu paragraf; pergantian baris dan tab diterjemahkan.
     */
    protected function runsToText(string $xml): string
    {
        $pattern = '#<w:t(?:\s[^>]*)?>(.*?)</w:t>|<w:(br|cr|tab)\b[^>]*/?>#s';

        if (! preg_match_all($pattern, $xml, $matches, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $html = '';

        foreach ($matches[0] as $index => $whole) {
            if ($matches[2][$index][1] === -1) {
                $text = html_entity_decode($matches[1][$index][0], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $html .= htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

                continue;
            }

            $html .= strtolower($matches[2][$index][0]) === 'tab'
                ? '<span class="tab"></span>'
                : '<br/>';
        }

        return $html;
    }

    /**
     * Properti run yang berlaku untuk seluruh paragraf: Format paragraf hanya
     * berlaku kalau semua run teksnya memakai format yang sama.
     */
    protected function runProperty(string $paragraph, string $property): bool
    {
        if (! preg_match_all('#<w:r(?:\s[^>]*)?>.*?</w:r>#s', $paragraph, $runs)) {
            return false;
        }

        $textRuns = 0;
        $matching = 0;

        foreach ($runs[0] as $run) {
            if (! str_contains($run, '<w:t')) {
                continue;
            }

            $textRuns++;

            if (preg_match('#<w:'.$property.'(?:\s[^>]*)?/>#', $run)) {
                $matching++;
            }
        }

        return $textRuns > 0 && $textRuns === $matching;
    }

    protected function paragraphProperty(string $paragraph): string
    {
        if (preg_match('#<w:pPr>.*?</w:pPr>#s', $paragraph, $match)) {
            return $match[0];
        }

        return '';
    }
}
