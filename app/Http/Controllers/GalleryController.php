<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Gallery::query()
            ->with(['ambalan', 'uploadedBy'])
            ->orderByDesc('created_at');

        if ($request->string('kategori')->isNotEmpty()) {
            $query->where('kategori', $request->string('kategori'));
        }

        $galleries = $query->paginate(20)->withQueryString();
        $categories = ['Umum', 'Kegiatan', 'Latihan', 'Outbound', 'Jambore', 'Peringatan'];

        return Inertia::render('Galleries/Index', [
            'galleries' => $galleries,
            'categories' => $categories,
            'filters' => $request->only(['kategori']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus', 'Anggota'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'image' => ['required', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'kategori' => ['required', 'string', 'max:100'],
        ]);

        $data['image'] = $request->file('image')->store('gallery', 'public');

        Gallery::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('galleries.index')->with('success', 'Foto berhasil diunggah.');
    }

    public function destroy(Request $request, Gallery $gallery): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        if ($gallery->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($gallery->image);
        }
        $gallery->delete();
        return redirect()->route('galleries.index')->with('success', 'Foto dihapus.');
    }
}
