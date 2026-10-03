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

    /**
     * Salinan lokal dari berkas yang tersimpan di disk remote. Dibersihkan
     * setelah permintaan selesai supaya folder sementara tidak menumpuk.
     *
     * @var array<int, string>
     */
    private array $temporaryCopies = [];

    public function __construct(
        private readonly DocxTemplate $docx,
        private readonly LetterValues $values,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $search = $this->stringInput($request, 'search');
        $jenis = $this->stringInput($request, 'jenis_surat');

        $letters = Letter::query()
            ->with(['ambalan', 'createdBy', 'template'])
            ->when($search !== '', fn ($query) => $query->where('perihal', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($jenis !== '', fn ($query) => $query->where('jenis_surat', $jenis))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Letters/Index', [
            'letters' => $letters,
            'templates' => $this->templatesPayload(),
            'ambalans' => Ambalan::orderBy('nama')->get(['id', 'nama']),
            'jenisOptions' => ['Masuk', 'Keluar', 'Keputusan'],
            'filters' => [
                'search' => $search,
                'jenis_surat' => $jenis,
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
        $path = $this->storeAttachment($request->file('file'));

        try {
            $letter = Letter::create([
                ...$data,
                'file_path' => $path,
                'created_by_user_id' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            if ($path !== null) {
                $this->deleteStored($path);
            }

            return $this->redirectTo($request, 'letters.index', 'Surat gagal disimpan karena kesalahan basis data. Silakan coba lagi.', 'error');
        }

        $this->log($request, 'letter.created', Letter::class, $letter->id);

        return $this->redirectTo($request, 'letters.index', 'Surat berhasil disimpan.');
    }

    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        $data = $request->letterData();
        $previous = $letter->file_path;
        $path = $previous;

        if ($request->hasFile('file')) {
            $path = $this->storeAttachment($request->file('file'));
        }

        try {
            $letter->update([...$data, 'file_path' => $path]);
        } catch (Throwable $exception) {
            report($exception);

            if ($path !== $previous && $path !== null) {
                $this->deleteStored($path);
            }

            return $this->redirectTo($request, 'letters.index', 'Surat gagal diperbarui karena kesalahan basis data. Silakan coba lagi.', 'error');
        }

        // Lampiran lama baru dihapus setelah record berhasil ditulis, jadi
        // kegagalan simpan tidak ikut menghilangkan berkas yang masih dirujuk.
        if ($previous !== null && $previous !== $path) {
            $this->deleteStored($previous);
        }

        $this->log($request, 'letter.updated', Letter::class, $letter->id);

        return $this->redirectTo($request, 'letters.index', 'Surat berhasil diperbarui.');
    }

    public function destroy(Request $request, Letter $letter): RedirectResponse
    {
        $this->authorizeManager($request);

        if ($letter->file_path) {
            $this->deleteStored($letter->file_path);
        }

        $letter->delete();

        $this->log($request, 'letter.archived', Letter::class, $letter->id);

        return $this->redirectTo($request, 'letters.index', 'Surat berhasil diarsipkan.');
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

            return $this->renderResponse('Pratinjau - '.$letter->perihal, $body, $unfilled);
        } catch (Throwable $exception) {
            report($exception);

            return $this->renderError('Pratinjau gagal: '.$exception->getMessage());
        } finally {
            $this->cleanupTemporaryCopies();
        }
    }

    public function print(Letter $letter): InertiaResponse
    {
        $letter->load(['ambalan', 'template']);

        try {
            [$body, $unfilled] = $this->renderLetter($letter, $letter->placeholder_values ?? []);
            $problem = null;
        } catch (Throwable $exception) {
            report($exception);

            // Template hilang (misalnya karena disk sementara di hosting) tidak
            // boleh membuat halaman cetak error 422. Isi tetap ditampilkan
            // dari tata letak bawaan supaya surat masih bisa dibaca, tapi
            // pengguna diberi tahu hasilnya belum tentu sama dengan ekspor.
            $body = $this->defaultBody($letter, $letter->placeholder_values ?? []);
            $unfilled = [];
            $problem = 'Template surat tidak dapat dibaca ('.$exception->getMessage().'). '
                .'Isi di bawah memakai tata letak bawaan, jadi belum tentu sama dengan berkas hasil ekspor.';
        } finally {
            $this->cleanupTemporaryCopies();
        }

        return Inertia::render('Letters/Print', [
            'letter' => $letter,
            'body' => $body,
            'unfilled' => $unfilled,
            'problem' => $problem,
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
        } finally {
            $this->cleanupTemporaryCopies();
        }
    }

    public function download(Letter $letter): SymfonyResponse
    {
        abort_unless($letter->file_path, 404, 'Lampiran surat tidak ditemukan.');

        $extension = pathinfo($letter->file_path, PATHINFO_EXTENSION) ?: 'pdf';

        // Berkas lampiran bisa hilang, misalnya karena disk sementara di
        // hosting. Tanpa pengecekan ini, unduhan gagal jadi 500.
        abort_unless($this->disk()->exists($letter->file_path), 404, 'Berkas lampiran tidak ada di penyimpanan.');

        return $this->disk()->download($letter->file_path, $this->filename($letter, $extension));
    }

    public function downloadTemplate(Request $request, LetterTemplate $template): SymfonyResponse
    {
        $this->authorizeManager($request);

        abort_unless($template->file_path, 404, 'Berkas template tidak ditemukan.');
        abort_unless($this->disk()->exists($template->file_path), 404, 'Berkas template tidak ada di penyimpanan.');

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
            'storage' => $this->storageStatus(),
        ]);
    }

    public function storeTemplate(LetterTemplateRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $this->validateDocxContent($file);

        $path = $file->store(config('letters.templates_directory', 'letter-templates'), $this->diskName());

        if (! is_string($path) || $path === '') {
            return $this->redirectTo(
                $request,
                'letters.templates',
                'Template gagal disimpan. Penyimpanan '
                .$this->diskName().' tidak dapat ditulis; periksa konfigurasi LETTERS_DISK.',
                'error'
            );
        }

        try {
            $placeholders = $this->readPlaceholders($path);
        } catch (Throwable $exception) {
            report($exception);
            $this->deleteStored($path);

            return $this->redirectTo(
                $request,
                'letters.templates',
                'Template tidak dapat dibaca. Pastikan berkas .docx tidak rusak dan dibuat dengan Microsoft Word.',
                'error'
            );
        } finally {
            $this->cleanupTemporaryCopies();
        }

        if ($placeholders === []) {
            $this->deleteStored($path);

            return $this->redirectTo(
                $request,
                'letters.templates',
                'Template tidak berisi penanda ${perihal}. Tambahkan penanda pada dokumen, contoh ${perihal}, lalu unggah ulang.',
                'error'
            );
        }

        try {
            $template = LetterTemplate::create([
                'name' => $request->string('name')->toString(),
                'file_path' => $path,
                'description' => $request->input('description') ?: null,
                'placeholders' => $placeholders,
                'created_by_user_id' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->deleteStored($path);

            return $this->redirectTo(
                $request,
                'letters.templates',
                'Template gagal disimpan karena kesalahan basis data. Silakan coba lagi.',
                'error'
            );
        }

        $this->log($request, 'template.created', LetterTemplate::class, $template->id);

        return $this->redirectTo(
            $request,
            'letters.templates',
            'Template surat berhasil disimpan dengan '.count($placeholders).' penanda.'
        );
    }

    public function destroyTemplate(Request $request, LetterTemplate $template): RedirectResponse
    {
        $this->authorizeManager($request);

        if ($template->file_path) {
            $this->deleteStored($template->file_path);
        }

        $template->delete();

        $this->log($request, 'template.deleted', LetterTemplate::class, $template->id);

        return $this->redirectTo($request, 'letters.templates', 'Template surat berhasil dihapus.');
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
        $placeholders = $letter->template?->placeholderList() ?? [];

        if ($letter->template && $letter->template->file_path && $placeholders !== []) {
            $resolved = $this->values->forTemplate($letter, $placeholders, $extra);

            return [
                $this->docx->toHtml($this->templatePath($letter->template), $resolved['values']),
                $resolved['unfilled'],
            ];
        }

        return [$this->defaultBody($letter, $extra), []];
    }

    /**
     * Isi surat dari tata letak bawaan, dipakai saat surat tidak berpola
     * templat dan saat templatnya tidak bisa dibaca.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function defaultBody(Letter $letter, array $extra = []): string
    {
        return view('letters.default-body', [
            'letter' => $letter,
            'ambalan' => $letter->ambalan,
            'extra' => $extra,
        ])->render();
    }

    protected function exportDocx(Letter $letter): SymfonyResponse
    {
        $placeholders = $letter->template?->placeholderList() ?? [];

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
        $body = $this->defaultBody($letter, $extra);

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
        // Permintaan JSON (misalnya dari pemeriksa halaman) harus menerima
        // pesan yang sama supaya alasannya tidak hilang di balik halaman error.
        if (request()->expectsJson()) {
            return response(['message' => $message], 422);
        }

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

    /**
     * Redirect setelah Inertia mengirim POST/PATCH/DELETE.
     *
     * Inertia meminta 303 supaya peramban mengikuti dengan GET. Kalau 302,
     * peramban mengulang permintaan dengan metode yang sama, dan halaman
     * dirender dua kali.
     */
    protected function redirectTo(Request $request, string $route, string $message, string $level = 'success'): RedirectResponse
    {
        $status = in_array($request->method(), ['POST', 'PATCH', 'DELETE'], true) ? 303 : 302;

        return redirect()->route($route, [], $status)->with($level, $message);
    }

    /**
     * Ringkasan kondisi penyimpanan template dan lampiran.
     *
     * Folder /tmp di Vercel hilang setiap kali fungsi dinyalakan ulang, jadi
     * berkas yang tersimpan di sana tidak bertahan. Kondisi ini ditampilkan di
     * halaman template supaya masalahnya terlihat, bukan baru terasa setelah
     * berkas hilang.
     *
     * @return array{disk: string, persistent: bool, writable: bool}
     */
    protected function storageStatus(): array
    {
        $config = (array) config('filesystems.disks.'.$this->diskName(), []);
        $root = (string) ($config['root'] ?? '');
        $remote = $this->isRemoteDisk();

        $ephemeral = ! $remote
            && (str_starts_with($root, '/tmp') || preg_match('#(^|[\\/])tmp[\\/]#i', $root) === 1);

        $writable = true;

        try {
            $probe = config('letters.templates_directory', 'letter-templates').'/.storage-check';
            $this->disk()->put($probe, 'cek');

            if (! $this->disk()->exists($probe)) {
                $writable = false;
            }

            $this->disk()->delete($probe);
        } catch (Throwable) {
            $writable = false;
        }

        return [
            'disk' => $this->diskName(),
            'persistent' => ! $ephemeral,
            'writable' => $writable,
        ];
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
     * hosting, jadi folder sementara sistem yang dipakai. Salinan lokal
     * dihapus lagi oleh cleanupTemporaryCopies().
     *
     * @return string Path lokal.
     */
    protected function localPath(string $storagePath): string
    {
        $disk = $this->disk();

        if (! $disk->exists($storagePath)) {
            abort(422, 'Berkas tidak ditemukan di penyimpanan.');
        }

        if (! $this->isRemoteDisk()) {
            return $disk->path($storagePath);
        }

        $path = $this->temporaryPath('docx');
        $source = $disk->readStream($storagePath);

        if (! is_resource($source)) {
            abort(422, 'Berkas tidak dapat dibaca dari penyimpanan.');
        }

        $target = @fopen($path, 'wb');

        if (! is_resource($target)) {
            fclose($source);

            abort(422, 'Folder sementara tidak dapat ditulis.');
        }

        $copied = stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        abort_if($copied === false, 422, 'Berkas tidak dapat disalin ke folder sementara.');

        $this->temporaryCopies[] = $path;

        return $path;
    }

    /**
     * Hapus salinan lokal dari berkas yang diambil dari disk remote.
     */
    protected function cleanupTemporaryCopies(): void
    {
        foreach ($this->temporaryCopies as $path) {
            @unlink($path);
        }

        $this->temporaryCopies = [];
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
        return $this->docx->placeholders($this->localPath($storagePath));
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

    /**
     * Simpan lampiran surat. Lampiran sebelumnya tidak dihapus di sini supaya
     * kegagalan penyimpanan tidak ikut menghilangkan berkas lama.
     */
    protected function storeAttachment(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $this->validateAttachmentContent($file);

        $path = $file->store(config('letters.attachments_directory', 'letters'), $this->diskName());

        if (! is_string($path) || $path === '') {
            throw new RuntimeException(
                'Lampiran gagal disimpan. Penyimpanan '.$this->diskName().' tidak dapat ditulis.'
            );
        }

        return $path;
    }

    /**
     * Nilai teks dari query string. Input larik (?search[]=x) dianggap kosong
     * supaya tidak membuat halaman error 500.
     */
    protected function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Karakter wildcard SQL LIKE pada isian pengguna diperlakukan sebagai teks
     * biasa, jadi "%" tidak membuat semua baris ikut cocok.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
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

    /**
     * Catat jejak audit. Kegagalan menulis audit tidak boleh membatalkan aksi
     * pengguna, jadi error ditelan dan hanya dilaporkan.
     */
    protected function log(Request $request, string $action, string $entityType, int $entityId): void
    {
        try {
            AuditLog::create([
                'actor_id' => $request->user()?->id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $request->ip(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Hapus berkas dari disk. Template dan lampiran tetap bisa dihapus walau
     * berkasnya hilang atau penyimpanannya sedang bermasalah.
     */
    protected function deleteStored(string $storagePath): void
    {
        try {
            $this->disk()->delete($storagePath);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
