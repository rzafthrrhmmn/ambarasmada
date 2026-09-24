<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\Member;
use App\Models\PengurusPosition;
use Barryvdh\DomPDF\PDF as DompdfPDF;
use Carbon\Carbon;
use Illuminate\Http\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PDF;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

class LetterController extends Controller
{
    public function index(Request $request): Response
    {
        $letters = Letter::query()
            ->with(['ambalan', 'createdBy', 'template'])
            ->when($request->string('search')->isNotEmpty(), fn ($q, $s) => $q->where('perihal', 'like', "%{$s}%"))
            ->when($request->string('jenis_surat')->isNotEmpty(), fn ($q, $t) => $q->where('jenis_surat', $t))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Letters/Index', [
            'letters' => $letters,
            'templates' => LetterTemplate::orderByDesc('created_at')->get(),
            'ambalans' => Ambalan::orderBy('nama')->get(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'jenis_surat' => $request->string('jenis_surat')->toString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'jenis_surat' => ['required', 'in:Masuk,Keluar,Keputusan'],
            'perihal' => ['required', 'string', 'max:255'],
            'isi_surat' => ['nullable', 'string'],
            'tujuan_pengirim' => ['required', 'string', 'max:150'],
            'tgl_surat' => ['required', 'date'],
            'waktu_kegiatan' => ['nullable', 'string', 'max:255'],
            'lokasi_kegiatan' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'template_id' => ['nullable', 'exists:letter_templates,id'],
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->validateFileContent($file);
            $path = $file->store('letters', 'public');
        }

        $letter = Letter::create([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nomor_surat' => $data['nomor_surat'],
            'jenis_surat' => $data['jenis_surat'],
            'perihal' => $data['perihal'],
            'isi_surat' => $data['isi_surat'] ?? null,
            'tujuan_pengirim' => $data['tujuan_pengirim'],
            'tgl_surat' => $data['tgl_surat'],
            'waktu_kegiatan' => $data['waktu_kegiatan'] ?? null,
            'lokasi_kegiatan' => $data['lokasi_kegiatan'] ?? null,
            'file_path' => $path,
            'template_id' => $data['template_id'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'letter.created',
            'entity_type' => Letter::class,
            'entity_id' => $letter->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('letters.index')->with('success', 'Surat berjaya disimpan.');
    }

    public function update(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'jenis_surat' => ['required', 'in:Masuk,Keluar,Keputusan'],
            'perihal' => ['required', 'string', 'max:255'],
            'isi_surat' => ['nullable', 'string'],
            'tujuan_pengirim' => ['required', 'string', 'max:150'],
            'tgl_surat' => ['required', 'date'],
            'waktu_kegiatan' => ['nullable', 'string', 'max:255'],
            'lokasi_kegiatan' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'template_id' => ['nullable', 'exists:letter_templates,id'],
        ]);

        $path = $letter->file_path;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->validateFileContent($file);
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $file->store('letters', 'public');
        }

        $letter->update([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nomor_surat' => $data['nomor_surat'],
            'jenis_surat' => $data['jenis_surat'],
            'perihal' => $data['perihal'],
            'isi_surat' => $data['isi_surat'] ?? null,
            'tujuan_pengirim' => $data['tujuan_pengirim'],
            'tgl_surat' => $data['tgl_surat'],
            'waktu_kegiatan' => $data['waktu_kegiatan'] ?? null,
            'lokasi_kegiatan' => $data['lokasi_kegiatan'] ?? null,
            'file_path' => $path,
            'template_id' => $data['template_id'] ?? null,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'letter.updated',
            'entity_type' => Letter::class,
            'entity_id' => $letter->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('letters.index')->with('success', 'Surat berjaya diperbarui.');
    }

    public function destroy(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        if ($letter->file_path) {
            Storage::disk('public')->delete($letter->file_path);
        }
        $letter->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'letter.archived',
            'entity_type' => Letter::class,
            'entity_id' => $letter->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('letters.index')->with('success', 'Surat berjaya diarsipkan.');
    }

    public function download(Letter $letter): BinaryFileResponse
    {
        abort_unless($letter->file_path, 404, 'Berkas surat tidak ditemukan.');

        $filename = "surat-{$letter->perihal}" . ($letter->file_path ? '.' . pathinfo($letter->file_path, PATHINFO_EXTENSION) : '.pdf');

        return Storage::disk('public')->download($letter->file_path, $filename);
    }

    public function print(Letter $letter): Response
    {
        return Inertia::render('Letters/Print', [
            'letter' => $letter,
        ]);
    }

    public function generate(Letter $letter, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $format = $request->input('format', 'pdf');

        if (! $letter->perihal || ! $letter->isi_surat) {
            return redirect()->back()->with('error', 'Isi surat belum diisi.');
        }

        $ambalan = $letter->ambalan;

        if (! $ambalan) {
            return redirect()->back()->with('error', 'Ambalan tidak ditemukan.');
        }

        if ($format === 'docx') {
            return $this->generateDocx($letter, $ambalan);
        }

        return $this->generatePdf($letter, $ambalan);
    }

    protected function generatePdf(Letter $letter, $ambalan): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->renderPdf($letter, $ambalan);

        return $pdf->download("surat-{$letter->perihal}.pdf");
    }

    protected function renderPdf(Letter $letter, $ambalan): DompdfPDF
    {
        $pradana = $this->resolvePradanaNames($letter->ambalan_id);

        $html = view('letters.pdf', array_merge([
            'letter' => $letter,
            'ambalan' => $ambalan,
        ], $pradana))->render();

        return PDF::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => true]);
    }

    public function preview(Request $request): \Illuminate\Http\Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'ambalan_id' => ['required', 'exists:ambalans,id'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'jenis_surat' => ['required', 'in:Masuk,Keluar,Keputusan'],
            'perihal' => ['required', 'string', 'max:255'],
            'isi_surat' => ['nullable', 'string'],
            'tujuan_pengirim' => ['required', 'string', 'max:150'],
            'tgl_surat' => ['required', 'date'],
            'waktu_kegiatan' => ['nullable', 'string', 'max:255'],
            'lokasi_kegiatan' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'template_id' => ['nullable', 'exists:letter_templates,id'],
        ]);

        $letter = new Letter($data);
        $ambalan = Ambalan::findOrFail($data['ambalan_id']);

        $pdf = $this->renderPdf($letter, $ambalan);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="preview-surat.pdf"');
    }

    protected function resolvePradanaNames(?int $ambalanId): array
    {
        $names = [
            'nama_pradana_putra' => '-',
            'nis_pradana_putra' => '-',
            'nama_pradana_putri' => '-',
            'nis_pradana_putri' => '-',
        ];

        if (! $ambalanId) {
            return $names;
        }

        $pradanaPutraPosId = PengurusPosition::where('code', 'pradana_putra')->value('id');
        $pradanaPutriPosId = PengurusPosition::where('code', 'pradana_putri')->value('id');

        if ($pradanaPutraPosId) {
            $putra = Member::where('ambalan_id', $ambalanId)
                ->whereHas('memberPositions', fn ($q) => $q->where('position_id', $pradanaPutraPosId))
                ->first();
            if ($putra) {
                $names['nama_pradana_putra'] = $putra->nama_lengkap;
                $names['nis_pradana_putra'] = $putra->nta ?? '-';
            }
        }

        if ($pradanaPutriPosId) {
            $putri = Member::where('ambalan_id', $ambalanId)
                ->whereHas('memberPositions', fn ($q) => $q->where('position_id', $pradanaPutriPosId))
                ->first();
            if ($putri) {
                $names['nama_pradana_putri'] = $putri->nama_lengkap;
                $names['nis_pradana_putri'] = $putri->nta ?? '-';
            }
        }

        return $names;
    }

    protected function resolveTemplateValues(Letter $letter, $ambalan): array
    {
        $pradana = $this->resolvePradanaNames($letter->ambalan_id);

        return [
            'nomor_surat' => $letter->nomor_surat ?? '-',
            'perihal' => $letter->perihal,
            'isi_surat' => $letter->isi_surat ?? '',
            'tujuan_pengirim' => $letter->tujuan_pengirim ?? '-',
            'tgl_surat' => $letter->tgl_surat ? Carbon::parse($letter->tgl_surat)->translatedFormat('d F Y') : '-',
            'waktu_kegiatan' => $letter->waktu_kegiatan ?? '-',
            'lokasi_kegiatan' => $letter->lokasi_kegiatan ?? '-',
            'nama_ambalan' => $ambalan->nama,
            'tanggal' => now()->translatedFormat('d F Y'),
            'jenis_surat' => $letter->jenis_surat ?? '-',
            'nama_pradana_putra' => $pradana['nama_pradana_putra'],
            'nis_pradana_putra' => $pradana['nis_pradana_putra'],
            'nama_pradana_putri' => $pradana['nama_pradana_putri'],
            'nis_pradana_putri' => $pradana['nis_pradana_putri'],
        ];
    }

    protected function generateDocx(Letter $letter, $ambalan): \Symfony\Component\HttpFoundation\Response
    {
        $filename = "surat-{$letter->perihal}.docx";

        if ($letter->template_id) {
            $template = $letter->template;
            if ($template && $template->file_path && preg_match('/\.docx$/i', $template->file_path)) {
                $templatePath = Storage::disk('public')->path($template->file_path);

                if (! file_exists($templatePath)) {
                    throw new \RuntimeException("File template tidak ditemukan: {$template->file_path}");
                }

                $values = $this->resolveTemplateValues($letter, $ambalan);

                return response()->streamDownload(function () use ($templatePath, $values, $filename) {
                    $this->streamModifiedDocx($templatePath, $values, $filename);
                }, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            }

            throw new \RuntimeException('Template tidak valid atau bukan file .docx');
        }

        return $this->generateDocxFromScratch($letter, $ambalan, $filename, storage_path("app/public/letters/generated/{$filename}"));
    }

    protected function streamModifiedDocx(string $templatePath, array $values, string $filename): void
    {
        $zip = new \ZipArchive;
        $zip->open($templatePath);

        $xml = $zip->getFromName('word/document.xml');
        $modifiedXml = $this->replaceTemplatePlaceholders($xml, $values);

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $newZip = new \ZipArchive;
        $newZip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === 'word/document.xml') {
                $newZip->addFromString($name, $modifiedXml);
            } else {
                $content = $zip->getFromIndex($i);
                $newZip->addFromString($name, $content === false ? '' : $content);
            }
        }
        $newZip->close();
        $zip->close();

        readfile($tempFile);
        unlink($tempFile);
    }

    protected function replaceTemplatePlaceholders(string $xml, array $values): string
    {
        $plainPos = 0;
        $xmlPosMap = [];
        $xmlLen = strlen($xml);
        for ($i = 0; $i < $xmlLen; $i++) {
            if ($xml[$i] === '<') {
                while ($i < $xmlLen && $xml[$i] !== '>') {
                    $i++;
                }
            } else {
                $xmlPosMap[$plainPos] = $i;
                $plainPos++;
            }
        }
        $xmlPosMap[$plainPos] = $xmlLen;

        $plainText = strip_tags($xml);

        preg_match_all('/\$\{([^}]+)\}/', $plainText, $matches, PREG_OFFSET_CAPTURE);

        $replacements = [];
        foreach ($matches[0] as $idx => $fullMatch) {
            $name = $matches[1][$idx][0];
            $value = $values[$name] ?? null;
            if ($value === null) {
                continue;
            }

            $plainStart = $fullMatch[1];
            $plainEnd = $plainStart + strlen($fullMatch[0]);

            $xmlStart = null;
            $xmlEnd = null;

            for ($p = $plainStart; $p >= 0; $p--) {
                if (isset($xmlPosMap[$p])) {
                    $xmlStart = $xmlPosMap[$p];
                    $wOpen = strrpos(substr($xml, 0, $xmlStart), '<w:t');
                    if ($wOpen !== false) {
                        $xmlStart = $wOpen;
                    }
                    break;
                }
            }

            for ($p = $plainEnd - 1; $p <= $plainPos; $p++) {
                if (isset($xmlPosMap[$p])) {
                    $xmlEnd = $xmlPosMap[$p] + 1;
                    $after = substr($xml, $xmlEnd);
                    if (preg_match('/<\/w:t>/', $after, $closeMatch, PREG_OFFSET_CAPTURE)) {
                        $xmlEnd += $closeMatch[0][1] + strlen('</w:t>');
                    }
                    break;
                }
            }

            if ($xmlStart !== null && $xmlEnd !== null) {
                $replacements[] = [$xmlStart, $xmlEnd, $value];
            }
        }

        usort($replacements, fn ($a, $b) => $b[0] - $a[0]);

        foreach ($replacements as [$start, $end, $value]) {
            $xml = substr($xml, 0, $start).'<w:t>'.$value.'</w:t>'.substr($xml, $end);
        }

        return $xml;
    }

    protected function generateDocxFromScratch(Letter $letter, $ambalan, string $filename, string $path): \Symfony\Component\HttpFoundation\Response
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection([
            'margin_top' => 1000,
            'margin_right' => 1000,
            'margin_bottom' => 1000,
            'margin_left' => 1000,
        ]);

        $styleFont = ['size' => 12, 'name' => 'DejaVu Sans'];
        $styleTitle = ['size' => 16, 'bold' => true, 'name' => 'DejaVu Sans'];

        $section->addText($ambalan->nama ?? 'Ambalan Pramuka', $styleTitle, ['align' => 'center']);
        $section->addText('Persuratan Digital', ['size' => 11, 'italic' => true, 'name' => 'DejaVu Sans'], ['align' => 'center']);
        $section->addText('---', ['size' => 10], ['align' => 'center']);

        $section->addTextBreak(1);
        $nomor = $letter->nomor_surat ?? '-';
        $tanggal = $letter->tgl_surat ? Carbon::parse($letter->tgl_surat)->translatedFormat('d F Y') : '-';
        $tujuan = $letter->tujuan_pengirim ?? '-';
        $waktu = $letter->waktu_kegiatan ?? null;
        $lokasi = $letter->lokasi_kegiatan ?? null;
        $pradana = $this->resolvePradanaNames($letter->ambalan_id);

        $section->addText("Nomor Surat: {$nomor}", $styleFont);
        $section->addText("Perihal: {$letter->perihal}", $styleFont);
        $section->addText("Tanggal: {$tanggal}", $styleFont);
        if ($waktu) {
            $section->addText("Waktu Kegiatan: {$waktu}", $styleFont);
        }
        if ($lokasi) {
            $section->addText("Lokasi Kegiatan: {$lokasi}", $styleFont);
        }
        $section->addText("Tujuan/Pengirim: {$tujuan}", $styleFont);
        $section->addText("Pradana Putra: {$pradana['nama_pradana_putra']}", $styleFont);
        $section->addText("NIS Pradana Putra: {$pradana['nis_pradana_putra']}", $styleFont);
        $section->addText("Pradana Putri: {$pradana['nama_pradana_putri']}", $styleFont);
        $section->addText("NIS Pradana Putri: {$pradana['nis_pradana_putri']}", $styleFont);

        $section->addTextBreak(2);
        $section->addText('Isi Surat:', ['size' => 12, 'bold' => true, 'name' => 'DejaVu Sans']);
        $section->addTextBreak(1);

        $paragraphStyle = ['spaceBefore' => 200, 'spaceAfter' => 200];
        $section->addText($letter->isi_surat, $styleFont, $paragraphStyle);

        $section->addTextBreak(3);
        $section->addText('Dikeluarkan pada: '.now()->translatedFormat('d F Y'), $styleFont);

        return response()->streamDownload(function () use ($phpWord) {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function templates(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $templates = LetterTemplate::query()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Letters/Templates', compact('templates'));
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        $this->validateFileContent($file);
        $path = $file->store('letter-templates', 'public');

        // Validate template placeholders
        $validation = $this->validateTemplatePlaceholders($path);
        if (! $validation['valid']) {
            Storage::disk('public')->delete($path);

            return back()->with('error', 'Template tidak valid: '.$validation['message']);
        }

        $template = LetterTemplate::create([
            'name' => $data['name'],
            'file_path' => $path,
            'description' => $data['description'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'template.created',
            'entity_type' => LetterTemplate::class,
            'entity_id' => $template->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('letters.templates')->with('success', 'Template surat berhasil disimpan.');
    }

    protected function validateTemplatePlaceholders(string $storagePath): array
    {
        $requiredPlaceholders = [
            'nomor_surat', 'perihal', 'isi_surat', 'tujuan_pengirim',
            'tgl_surat', 'waktu_kegiatan', 'lokasi_kegiatan',
            'nama_ambalan', 'tanggal', 'jenis_surat',
            'nama_pradana_putra', 'nis_pradana_putra',
            'nama_pradana_putri', 'nis_pradana_putri',
        ];

        try {
            $templatePath = Storage::disk('public')->path($storagePath);
            $templateProcessor = new TemplateProcessor($templatePath);
            $foundPlaceholders = $templateProcessor->getVariables();

            $missing = [];
            foreach ($requiredPlaceholders as $ph) {
                if (! in_array($ph, $foundPlaceholders)) {
                    $missing[] = '${'.$ph.'}';
                }
            }

            if ($missing) {
                return [
                    'valid' => false,
                    'message' => 'Placeholder hilang: '.implode(', ', $missing),
                ];
            }

            return ['valid' => true, 'message' => ''];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Gagal membaca template: '.$e->getMessage(),
            ];
        }
    }

    protected function validateFileContent(UploadedFile $file): void
    {
        $allowedMimes = [
            'application/pdf' => '%PDF',
            'image/jpeg' => "\xFF\xD8\xFF",
            'image/png' => "\x89PNG\r\n\x1A\n",
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'PK\x03\x04',
        ];

        $mime = $file->getMimeType();
        if (! isset($allowedMimes[$mime])) {
            throw new ValidationException(
                Factory::make()->make([], [], ['file' => 'Tipe file tidak diizinkan.'])
            );
        }

        $header = file_get_contents($file->getRealPath(), false, null, 0, 8);
        $expectedHeader = $allowedMimes[$mime];

        if (strpos($header, $expectedHeader) !== 0) {
            throw new ValidationException(
                Factory::make()->make([], [], ['file' => 'Konten file tidak sesuai dengan tipe yang dideklarasikan.'])
            );
        }
    }

    public function destroyTemplate(LetterTemplate $template): RedirectResponse
    {
        abort_unless(in_array(request()->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }
        $template->delete();

        AuditLog::create([
            'actor_id' => request()->user()->id,
            'action' => 'template.deleted',
            'entity_type' => LetterTemplate::class,
            'entity_id' => $template->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('letters.templates')->with('success', 'Template surat berhasil dihapus.');
    }
}
