<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\AuditLog;
use App\Models\LearningMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LearningMaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $materials = LearningMaterial::query()
            ->with(['ambalan', 'createdBy'])
            ->when($request->string('search')->isNotEmpty(), fn ($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->when($request->string('deskripsi')->isNotEmpty(), fn ($q, $d) => $q->where('deskripsi', $d))
            ->when($request->string('restricted')->isNotEmpty(), fn ($q, $r) => $q->where('is_restricted', $r === 'true'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'ambalans' => Ambalan::orderBy('nama')->get(),
            'categories' => LearningMaterial::distinct()->pluck('deskripsi')->filter()->values(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'deskripsi' => $request->string('deskripsi')->toString(),
                'restricted' => $request->string('restricted')->toString(),
            ],
        ]);
    }

    public function show(Request $request, LearningMaterial $material): Response
    {
        if ($material->is_restricted && ! in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true)) {
            abort(403, 'Anda tidak memiliki akses ke materi terbatas ini.');
        }

        $material->load(['ambalan', 'createdBy']);

        return Inertia::render('Materials/Show', [
            'material' => $material,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nama' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'konten' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,mp4,webm'],
            'is_restricted' => ['boolean'],
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('materials', 'public');
        }

        $material = LearningMaterial::create([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'konten' => $data['konten'] ?? null,
            'file_path' => $path,
            'created_by_user_id' => $request->user()->id,
            'is_restricted' => $data['is_restricted'] ?? false,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'material.created',
            'entity_type' => LearningMaterial::class,
            'entity_id' => $material->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Materi berjaya disimpan.');
    }

    public function update(Request $request, LearningMaterial $material): RedirectResponse
    {
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nama' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'konten' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,mp4,webm'],
            'is_restricted' => ['boolean'],
        ]);

        $path = $material->file_path;
        if ($request->hasFile('file')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('file')->store('materials', 'public');
        }

        $material->update([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'konten' => $data['konten'] ?? null,
            'file_path' => $path,
            'is_restricted' => $data['is_restricted'] ?? false,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'material.updated',
            'entity_type' => LearningMaterial::class,
            'entity_id' => $material->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Materi berjaya diperbarui.');
    }

    public function destroy(Request $request, LearningMaterial $material): RedirectResponse
    {
        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }
        $material->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'material.archived',
            'entity_type' => LearningMaterial::class,
            'entity_id' => $material->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Materi berjaya diarsipkan.');
    }

    public function download(Request $request, LearningMaterial $material): BinaryFileResponse
    {
        if ($material->is_restricted && ! in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true)) {
            abort(403, 'Anda tidak memiliki akses ke materi terbatas ini.');
        }

        abort_unless($material->file_path && Storage::disk('public')->exists($material->file_path), 404);

        return Storage::disk('public')->download($material->file_path);
    }
}
