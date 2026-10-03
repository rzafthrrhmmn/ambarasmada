<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterRequest;
use App\Http\Requests\LetterTemplateRequest;
use App\Models\Ambalan;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Support\Letters\DocxTemplate;
use App\Support\Letters\LetterValues;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PDF;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class LetterController extends Controller
{
    /** Roles yang boleh mengelola surat dan template. */
    private const MANAGERS = ['Admin', 'Pembina', 'Pengurus'];

    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function __construct(
        private readonly DocxTemplate $docx,
        private readonly LetterValues $values,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $letters = Letter::query()
            ->with(['ambalan', 'createdBy', 'template'])
            ->when($request->string('search')->isNotEmpty(), fn ($query, $search) => $query->where('perihal', 'like', "%{$search}%"))
            ->when($request->string('jenis_surat')->isNotEmpty(), fn ($query, $jenis) => $query->where('jenis_surat', $jenis))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Letters/Index', [
            'letters' => $letters,
            'templates' => $this->templatesPayload(),
            'ambalans' => Ambalan::orderBy('nama')->get(['id', 'nama']),
            'jenisOptions' => ['Masuk', 'Keluar', 'Keputusan'],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'jenis_surat' => $request->string('jenis_surat')->toString(),
            ],
        ]);
    }

    public function show(Letter $letter): InertiaResponse
    {
        $letter->load(['ambalan', 'template', 'createdBy']);
        $placeholders = $letter->template?->placeholderList() ?? [];
        $resolved = $this->values->forTemplate($letter, $placeholders, $letter->placeholder_values ?? []);

        return Inertia::render('Letters/Show', [
            'letter' => $letter,
            'placeholders' => $placeholders,
            'resolved' => $resolved['values'],
            'unfilled' => $resolved['unfilled'],
            'canGenerate' => $this->canGenerate($letter),
        ]);
    }

    public function store(LetterRequest $request): RedirectResponse
    {
        $data = $request->letterData();

        $letter = Letter::create([
            ...$data,
            'file_path' => $this->storeAttachment($request->file('file')),
            'created_by_user_id' => $request->user()->id,
        ]);

        $this->log($request, 'letter.created', Letter::class, $letter->id);

        return redirect()->route('letters.index')->with('success', 'Surat berhasil disimpan.');
    }

    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        $data = $request->letterData();
        $path = $letter->file_path;

        if ($request->hasFile('file')) {
            $path = $this->storeAttachment($request->file('file'), $path);
        }

        $letter->update([...$data, 'file_path' => $path]);

        $this->log($request, 'letter.updated', Letter::class, $letter->id);

        return redirect()->route('letters.index')->with('success', 'Surat berhasil diperbarui.');
    }

    public function destroy(Request $request, Letter $letter): RedirectResponse
    {
        $this->authorizeManager($request);

        if ($letter->file_path) {
            $this->disk()->delete($letter->file_path);
        }

        $letter->delete();

        $this->log($request, 'letter.archived', Letter::class, $letter->id);

        return redirect()->route('letters.index')->with('success', 'Surat berhasil diarsipkan.');
    }

    /**
     * Pratinjau surat sebelum disimpan: memakai templat .docx yang dipilih,
     * atau tata letak bawaan bila surat tidak berpola templat.
     */
    public function preview(Request $request): Response
    {
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'integer', 'exists:ambalans,id'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'jenis_surat' => ['nullable', 'string', 'max:50'],
            'perihal' => ['required', 'string', 'max:255'],
            'isi_surat' => ['nullable', 'string', 'max:20000'],
            'tujuan_pengirim' => ['nullable', 'string', 'max:150'],
            'tgl_surat' => ['nullable', 'date'],
            'waktu_kegiatan' => ['nullable', 'string', 'max:255'],
            'lokasi_kegiatan' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:letter_templates,id'],
            'placeholder_values' => ['nullable', 'array'],
            'placeholder_values.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $letter = $this->draft($data);

        try {
            [$body, $unfilled] = $this->renderLetter($letter, $data['placeholder_values'] ?? []);
        } catch (Throwable $exception) {
            report($exception);

            return $this->renderError('Pratinjau gagal: '.$exception->getMessage());
        }

        return $this->renderResponse('Pratinjau - '.$letter->perihal, $body, $unfilled);
    }

    public function print(Letter $letter): InertiaResponse
    {
        $letter->load(['ambalan', 'template']);
        [$body, $unfilled] = $this->renderLetter($letter, $letter->placeholder_values ?? []);

        return Inertia::render('Letters/Print', [
            'letter' => $letter,
            'body' => $body,
            'unfilled' => $unfilled,
        ]);
    }

    /**
     * Ekspor surat ke .docx (isi templat pengguna) atau .pdf.
     */
    public function generate(Request $request, Letter $letter): SymfonyResponse
    {
        $this->authorizeManager($request);

        $format = Str::lower($request->query('format', 'pdf'));

        if (! in_array($format, ['docx', 'pdf'], true)) {
            return $this->generateError('Format tidak dikenal. Gunakan docx atau pdf.');
        }

        if (! $letter->perihal) {
            return $this->generateError('Perihal surat belum diisi.');
        }

        $letter->loadMissing(['ambalan', 'template']);

        try {
            return $format === 'docx'
                ? $this->exportDocx($letter)
                : $this->exportPdf($letter);
        } catch (Throwable $exception) {
            report($exception);

            return $this->generateError('Ekspor gagal: '.$exception->getMessage());
        }
    }

    public function download(Letter $letter): SymfonyResponse
    {
        abort_unless($letter->file_path, 404, 'Lampiran surat tidak ditemukan.');

        $extension = pathinfo($letter->file_path, PATHINFO_EXTENSION) ?: 'pdf';

        return $this->disk()->download($letter->file_path, $this->filename($letter, $extension));
    }

    public function downloadTemplate(Request $request, LetterTemplate $template): SymfonyResponse
    {
        $this->authorizeManager($request);

        abort_unless($template->file_path, 404, 'Berkas template tidak ditemukan.');

        $filename = (Str::slug($template->name) ?: 'template').'.docx';

        return $this->disk()->download($template->file_path, $filename, [
            'Content-Type' => static::DOCX_MIME,
        ]);
    }

    public function templates(Request $request): InertiaResponse
    {
        $this->authorizeManager($request);

        return Inertia::render('Letters/Templates', [
            'templates' => LetterTemplate::query()
                ->with('createdBy')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (LetterTemplate $template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'placeholders' => $template->placeholderList(),
                    'fields' => $this->values->formFields($template->placeholderList()),
                    'download_url' => route('letters.templates.download', $template),
                    'created_at' => $template->created_at?->toIso8601String(),
                ]),
            'catalog' => $this->values->catalog(),
        ]);
    }

    public function storeTemplate(LetterTemplateRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $this->validateDocxContent($file);

        $path = $file->store(config('letters.templates_directory', 'letter-templates'), $this->diskName());

        if (! is_string($path) || $path === '') {
            return back()->with(
                'error',
                'Template gagal disimpan. Penyimpanan '
                .$this->diskName().' tidak dapat ditulis; periksa konfigurasi LETTERS_DISK.'
            );
        }

        $placeholders = $this->readPlaceholders($path);

        if ($placeholders === []) {
            $this->disk()->delete($path);

            return back()->with(
                'error',
                'Template tidak berisi penanda ${perihal}. Tambahkan penanda pada dokumen, contoh ${perihal}, lalu unggah ulang.'
            );
        }

        $template = LetterTemplate::create([
            'name' => $request->string('name')->toString(),
            'file_path' => $path,
            'description' => $request->input('description') ?: null,
            'placeholders' => $placeholders,
            'created_by_user_id' => $request->user()->id,
        ]);

        $this->log($request, 'template.created', LetterTemplate::class, $template->id);

        return redirect()
            ->route('letters.templates')
            ->with('success', 'Template surat berhasil disimpan dengan '.count($placeholders).' penanda.');
    }

    public function destroyTemplate(Request $request, LetterTemplate $template): RedirectResponse
    {
        $this->authorizeManager($request);

        if ($template->file_path) {
            $this->disk()->delete($template->file_path);
        }

        $template->delete();

        $this->log($request, 'template.deleted', LetterTemplate::class, $template->id);

        return redirect()->route('letters.templates')->with('success', 'Template surat berhasil dihapus.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function templatesPayload(): array
    {
        return LetterTemplate::orderByDesc('created_at')
            ->get()
            ->map(fn (LetterTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'placeholders' => $template->placeholderList(),
                'fields' => $this->values->formFields($template->placeholderList()),
                'download_url' => route('letters.templates.download', $template),
                'created_at' => $template->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Susun isi surat: dari templat .docx bila ada, atau dari tata letak bawaan.
     *
     * @param  array<string, mixed>  $extra
     * @return array{0: string, 1: array<int, string>}
     */
    protected function renderLetter(Letter $letter, array $extra = []): array
    {
        $placeholders = $letter->template?->placeholders ?? [];

        if ($letter->template && $letter->template->file_path && $placeholders !== []) {
            $resolved = $this->values->forTemplate($letter, $placeholders, $extra);

            return [
                $this->docx->toHtml($this->templatePath($letter->template), $resolved['values']),
                $resolved['unfilled'],
            ];
        }

        return [
            view('letters.default-body', [
                'letter' => $letter,
                'ambalan' => $letter->ambalan,
                'extra' => $extra,
            ])->render(),
            [],
        ];
    }

    protected function exportDocx(Letter $letter): SymfonyResponse
    {
        $placeholders = $letter->template?->placeholders ?? [];

        if (! $letter->template?->file_path || $placeholders === []) {
            return $this->exportDocxFromScratch($letter);
        }

        $resolved = $this->values->forTemplate($letter, $placeholders, $letter->placeholder_values ?? []);
        $path = $this->temporaryPath('docx');

        $this->docx->fill($this->templatePath($letter->template), $resolved['values'], $path);

        return response()
            ->download($path, $this->filename($letter, 'docx'), ['Content-Type' => static::DOCX_MIME])
            ->deleteFileAfterSend();
    }

    /**
     * Susun .docx dari nol untuk surat yang tidak memakai templat.
     */
    protected function exportDocxFromScratch(Letter $letter): SymfonyResponse
    {
        $extra = $letter->placeholder_values ?? [];

        $body = view('letters.default-body', [
            'letter' => $letter,
            'ambalan' => $letter->ambalan,
            'extra' => $extra,
        ])->render();

        $document = new PhpWord;
        $section = $document->addSection([
            'margin_top' => 1000,
            'margin_right' => 1000,
            'margin_bottom' => 1000,
            'margin_left' => 1000,
        ]);

        $font = ['name' => 'DejaVu Sans', 'size' => 11];

        foreach ($this->blocks($body) as $block) {
            $section->addText($block, $font, ['spaceAfter' => 160]);
        }

        $path = $this->temporaryPath('docx');
        IOFactory::createWriter($document, 'Word2007')->save($path);

        return response()
            ->download($path, $this->filename($letter, 'docx'), ['Content-Type' => static::DOCX_MIME])
            ->deleteFileAfterSend();
    }

    protected function exportPdf(Letter $letter): SymfonyResponse
    {
        [$body, $unfilled] = $this->renderLetter($letter, $letter->placeholder_values ?? []);

        $pdf = PDF::loadHTML(view('letters.render', [
            'title' => 'Surat - '.$letter->perihal,
            'body' => $body,
            'unfilled' => $unfilled,
        ])->render())
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $content = $pdf->output();

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($letter, 'pdf').'"',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    /**
     * Surat While-dibuat dari isian form, tanpa menyentuh basis data.
     *
     * @param  array<string, mixed>  $data
     */
    protected function draft(array $data): Letter
    {
        $letter = new Letter;
        $letter->forceFill([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nomor_surat' => $data['nomor_surat'] ?? null,
            'jenis_surat' => $data['jenis_surat'] ?? 'Masuk',
            'perihal' => $data['perihal'],
            'isi_surat' => $data['isi_surat'] ?? null,
            'tujuan_pengirim' => $data['tujuan_pengirim'] ?? null,
            'tgl_surat' => $data['tgl_surat'] ?? null,
            'waktu_kegiatan' => $data['waktu_kegiatan'] ?? null,
            'lokasi_kegiatan' => $data['lokasi_kegiatan'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'placeholder_values' => $data['placeholder_values'] ?? [],
        ]);

        $letter->setRelation('ambalan', isset($data['ambalan_id']) ? Ambalan::find($data['ambalan_id']) : null);
        $letter->setRelation('template', isset($data['template_id']) ? LetterTemplate::find($data['template_id']) : null);

        return $letter;
    }

    protected function renderResponse(string $title, string $body, array $unfilled): Response
    {
        return response(view('letters.render', compact('title', 'body', 'unfilled'))->render(), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    protected function renderError(string $message): Response
    {
        return response(view('letters.render', [
            'title' => 'Pratinjau gagal',
            'body' => '<p class="catatan">'.e($message).'</p>',
            'unfilled' => [],
        ])->render(), 422, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Ringkasan isi surat dari HTML tata letak bawaan, untuk .docx tanpa templat.
     *
     * @return array<int, string>
     */
    protected function blocks(string $html): array
    {
        $text = html_entity_decode(
            strip_tags(str_replace(['<br/>', '<br>', '</p>', '</td>'], "\n", $html)),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        $blocks = array_values(array_filter(array_map('trim', preg_split('/\n+/', $text) ?: [])));

        return $blocks === [] ? ['-'] : $blocks;
    }

    protected function canGenerate(Letter $letter): bool
    {
        return $letter->perihal !== null && $letter->perihal !== '';
    }

    protected function templatePath(LetterTemplate $template): string
    {
        $path = $this->localPath((string) $template->file_path);

        abort_unless(is_file($path), 422, 'Berkas template tidak ditemukan di penyimpanan.');

        return $path;
    }

    /**
     * Disk tempat template dan lampiran disimpan.
     */
    protected function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    protected function diskName(): string
    {
        $disk = trim((string) config('letters.disk', ''));

        return $disk !== '' ? $disk : 'public';
    }

    /**
     * Path lokal yang bisa dibaca untuk berkas yang disimpan.
     *
     * Disk remote (S3 dan sejenisnya) tidak punya path lokal, jadi berkasnya
     * disalin ke folder sementara lebih dulu. Folder aplikasi hanya-baca di
     * hosting, jadi folder sementara sistem yang dipakai.
     *
     * @return string Path lokal; hapus sendiri setelah selesai dipakai.
     */
    protected function localPath(string $storagePath): string
    {
        $disk = $this->disk();

        if (! $disk->exists($storagePath)) {
            abort(422, 'Berkas tidak ditemukan di penyimpanan.');
        }

        if ($this->isRemoteDisk()) {
            $path = $this->temporaryPath('docx');
            $disk->writeStream($path, fopen($disk->readStream($storagePath), 'r'));

            return $path;
        }

        return $disk->path($storagePath);
    }

    protected function isRemoteDisk(): bool
    {
        return in_array($this->diskName(), ['s3', 's3v3'], true)
            || str_starts_with((string) config("filesystems.disks.{$this->diskName()}.driver"), 's3');
    }

    /**
     * @return array<int, string>
     */
    protected function readPlaceholders(string $storagePath): array
    {
        try {
            $path = $this->localPath($storagePath);

            $found = $this->docx->placeholders($path);
        } catch (Throwable) {
            return [];
        }

        if (! $this->isRemoteDisk()) {
            return $found;
        }

        @unlink($path);

        return $found;
    }

    /**
     * Berkas sementara untuk unduhan. sys_get_temp_dir() dipakai karena
     * folder aplikasi bisa hanya-baca di hosting (Vercel).
     */
    protected function temporaryPath(string $extension): string
    {
        $directory = sys_get_temp_dir().'/pwa-letters';

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.'/'.Str::random(40).'.'.$extension;
    }

    protected function filename(Letter $letter, string $extension): string
    {
        $slug = Str::slug($letter->perihal ?: 'surat');

        return ($slug === '' ? 'surat' : $slug).'-'.$letter->id.'.'.$extension;
    }

    protected function storeAttachment(?UploadedFile $file, ?string $previous = null): ?string
    {
        if (! $file) {
            return $previous;
        }

        $this->validateAttachmentContent($file);

        if ($previous) {
            $this->disk()->delete($previous);
        }

        $path = $file->store(config('letters.attachments_directory', 'letters'), $this->diskName());

        if (! is_string($path) || $path === '') {
            throw new RuntimeException(
                'Lampiran gagal disimpan. Penyimpanan '.$this->diskName().' tidak dapat ditulis.'
            );
        }

        return $path;
    }

    protected function validateAttachmentContent(UploadedFile $file): void
    {
        $allowed = [
            'application/pdf' => '%PDF',
            'image/jpeg' => "\xFF\xD8\xFF",
            'image/png' => "\x89PNG\r\n\x1A\n",
        ];

        $mime = $file->getMimeType();

        abort_unless(isset($allowed[$mime]), 422, 'Lampiran harus berupa PDF atau gambar (JPG/PNG).');

        $header = (string) file_get_contents($file->getRealPath(), false, null, 0, 8);

        abort_if(! str_starts_with($header, $allowed[$mime]), 422, 'Isi berkas tidak sesuai dengan tipe yang diunggah.');
    }

    protected function validateDocxContent(UploadedFile $file): void
    {
        $header = (string) file_get_contents($file->getRealPath(), false, null, 0, 4);

        abort_unless(str_starts_with($header, "PK\x03\x04"), 422, 'Berkas template bukan .docx yang valid.');
    }

    protected function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, self::MANAGERS, true), 403);
    }

    protected function generateError(string $message): SymfonyResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->route('letters.index')->with('error', $message);
    }

    protected function log(Request $request, string $action, string $entityType, int $entityId): void
    {
        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $request->ip(),
        ]);
    }
}
