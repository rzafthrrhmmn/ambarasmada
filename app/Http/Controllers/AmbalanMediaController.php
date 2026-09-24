<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\AmbalanMedia;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AmbalanMediaController extends Controller
{
    public function index(Request $request): Response
    {
        $medias = AmbalanMedia::query()
            ->with(['ambalan', 'createdBy'])
            ->when($request->string('search')->isNotEmpty(), fn ($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Medias/Index', [
            'medias' => $medias,
            'ambalans' => Ambalan::orderBy('nama')->get(),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(AmbalanMedia $media): Response
    {
        $media->load(['ambalan', 'createdBy']);

        return Inertia::render('Medias/Show', [
            'media' => $media,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nama' => ['required', 'string', 'max:200'],
            'lirik' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:mp3,wav,mp4,webm,mpeg'],
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('medias', 'public');
        }

        $media = AmbalanMedia::create([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nama' => $data['nama'],
            'lirik' => $data['lirik'] ?? null,
            'file_path' => $path,
            'created_by_user_id' => $request->user()->id,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'media.created',
            'entity_type' => AmbalanMedia::class,
            'entity_id' => $media->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('medias.index')->with('success', 'Media mars ambalan berjaya disimpan.');
    }

    public function update(Request $request, AmbalanMedia $media): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nama' => ['required', 'string', 'max:200'],
            'lirik' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:mp3,wav,mp4,webm,mpeg'],
        ]);

        $path = $media->file_path;
        if ($request->hasFile('file')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('file')->store('medias', 'public');
        }

        $media->update([
            'ambalan_id' => $data['ambalan_id'] ?? null,
            'nama' => $data['nama'],
            'lirik' => $data['lirik'] ?? null,
            'file_path' => $path,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'media.updated',
            'entity_type' => AmbalanMedia::class,
            'entity_id' => $media->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('medias.index')->with('success', 'Media mars ambalan berjaya diperbarui.');
    }

    public function destroy(Request $request, AmbalanMedia $media): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        if ($media->file_path) {
            Storage::disk('public')->delete($media->file_path);
        }
        $media->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'media.archived',
            'entity_type' => AmbalanMedia::class,
            'entity_id' => $media->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('medias.index')->with('success', 'Media mars ambalan berjaya diarsipkan.');
    }
}