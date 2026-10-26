<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        $stored = $request->file('image')->store('gallery', 'public');
        abort_unless($stored, 500, 'Gagal menyimpan berkas galeri.');

        $data['image'] = $stored;

        Gallery::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::value('id') ?? 1,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('galleries.index')->with('success', 'Foto berhasil diunggah.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        // Otorisasi dicek SEBELUM resolusi model agar anggota yang mencoba
        // menghapus gallery milik orang lain (atau yang tidak ada) menerima 403
        // alih-alih 404 dari implicit route model binding.
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $gallery = Gallery::find($id);
        abort_if(!$gallery, 404);

        if ($gallery->image) {
            Storage::disk('public')->delete($gallery->image);
        }
        $gallery->delete();

        return redirect()->route('galleries.index')->with('success', 'Foto dihapus.');
    }
}
