<?php

namespace App\Http\Controllers;

use App\Models\ActivityGuide;
use App\Models\Ambalan;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityGuideController extends Controller
{
    public function index(Request $request): Response
    {
        $guides = ActivityGuide::query()
            ->with(['ambalan', 'createdBy'])
            ->when($request->string('kecamatan')->isNotEmpty(), fn ($q, $k) => $q->where('kecamatan', $k))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Guides/Index', [
            'guides' => $guides,
            'ambalans' => Ambalan::orderBy('nama')->get(),
            'kecamatanOptions' => ['PTA', 'Bantara', 'Upabuklat', 'Upatuplat', 'Lainnya'],
            'filters' => [
                'kecamatan' => $request->string('kecamatan')->toString(),
            ],
        ]);
    }

    public function show(ActivityGuide $guide): Response
    {
        $guide->load(['ambalan', 'createdBy']);

        return Inertia::render('Guides/Show', [
            'guide' => $guide,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'kecamatan' => ['required', 'in:PTA,Bantara,Upabuklat,Upatuplat,Lainnya'],
            'nama' => ['required', 'string', 'max:200'],
            'teks_susunan_upacara' => ['nullable', 'string'],
            'checklist_perlengkapan' => ['nullable', 'array'],
            'checklist_perlengkapan.*' => ['string', 'max:255'],
        ]);

        $guide = ActivityGuide::create([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'kecamatan' => $data['kecamatan'],
            'nama' => $data['nama'],
            'teks_susunan_upacara' => $data['teks_susunan_upacara'] ?? null,
            'checklist_perlengkapan' => $data['checklist_perlengkapan'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'guide.created',
            'entity_type' => ActivityGuide::class,
            'entity_id' => $guide->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('guides.index')->with('success', 'Panduan kegiatan berjaya disimpan.');
    }

    public function update(Request $request, ActivityGuide $guide): RedirectResponse
    {
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'kecamatan' => ['required', 'in:PTA,Bantara,Upabuklat,Upatuplat,Lainnya'],
            'nama' => ['required', 'string', 'max:200'],
            'teks_susunan_upacara' => ['nullable', 'string'],
            'checklist_perlengkapan' => ['nullable', 'array'],
            'checklist_perlengkapan.*' => ['string', 'max:255'],
        ]);

        $guide->update([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'kecamatan' => $data['kecamatan'],
            'nama' => $data['nama'],
            'teks_susunan_upacara' => $data['teks_susunan_upacara'] ?? null,
            'checklist_perlengkapan' => $data['checklist_perlengkapan'] ?? null,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'guide.updated',
            'entity_type' => ActivityGuide::class,
            'entity_id' => $guide->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('guides.index')->with('success', 'Panduan kegiatan berjaya diperbarui.');
    }

    public function destroy(Request $request, ActivityGuide $guide): RedirectResponse
    {
        $guide->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'guide.archived',
            'entity_type' => ActivityGuide::class,
            'entity_id' => $guide->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('guides.index')->with('success', 'Panduan kegiatan berjaya diarsipkan.');
    }
}
