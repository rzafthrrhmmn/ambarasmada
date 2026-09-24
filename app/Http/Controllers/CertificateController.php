<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $query = Certificate::query()
            ->with(['member', 'issuedBy'])
            ->orderByDesc('tanggal_diterbitkan');

        if ($request->string('jenis')->isNotEmpty()) {
            $query->where('jenis', $request->string('jenis'));
        }
        if ($request->string('status')->isNotEmpty()) {
            $query->where('status', $request->string('status'));
        }

        $certificates = $query->paginate(15)->withQueryString();

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
            'filters' => $request->only(['jenis', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'jenis' => ['required', 'string', 'max:255'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_diterbitkan' => ['required', 'date'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $nomor = 'SERT-' . now()->format('Ymd') . '-' . str_pad((string) Certificate::count() + 1, 5, '0', STR_PAD_LEFT);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('certificates', 'public');
        }

        Certificate::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'nomor_sertifikat' => $nomor,
            'issued_by' => $request->user()->id,
            'tanggal_diterbitkan' => $data['tanggal_diterbitkan'],
            'status' => 'Diterbitkan',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'certificate.created',
            'entity_type' => Certificate::class,
            'entity_id' => $nomor,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('certificates.index')->with('success', 'Sertifikat berhasil diterbitkan.');
    }

    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'status' => ['required', 'in:Diterbitkan,Dibatalkan'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $certificate->update($data);

        return back()->with('success', 'Sertifikat diperbarui.');
    }

    public function download(Certificate $certificate): Response
    {
        abort_unless($certificate->file_path, 404);
        return response()->download(storage_path('app/public/' . $certificate->file_path));
    }

    public function show(Certificate $certificate): Response
    {
        $certificate->load(['member.user', 'issuedBy', 'ambalan']);
        return Inertia::render('Certificates/Show', ['certificate' => $certificate]);
    }
}
