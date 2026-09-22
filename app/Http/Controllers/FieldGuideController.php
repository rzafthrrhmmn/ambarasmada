<?php

namespace App\Http\Controllers;

use App\Models\FieldGuide;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FieldGuideController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FieldGuide::query()
            ->with(['ambalan', 'createdBy'])
            ->orderByDesc('created_at');

        if ($request->string('kategori')->isNotEmpty()) {
            $query->where('kategori', $request->string('kategori'));
        }

        $guides = $query->paginate(20)->withQueryString();
        $categories = ['Tanda Isyarat', 'Simpul', 'Teknik Lapangan', 'P3K', 'Peraturan', 'Lainnya'];

        return Inertia::render('FieldGuides/Index', [
            'guides' => $guides,
            'categories' => $categories,
            'filters' => $request->only(['kategori']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus', 'Anggota'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'kategori' => ['required', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:100'],
        ]);

        FieldGuide::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('field-guides.index')->with('success', 'Panduan ditambahkan.');
    }

    public function update(Request $request, FieldGuide $guide): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'kategori' => ['required', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:100'],
        ]);

        $guide->update($data);

        return back()->with('success', 'Panduan diperbarui.');
    }

    public function destroy(FieldGuide $guide): RedirectResponse
    {
        $guide->delete();
        return redirect()->route('field-guides.index')->with('success', 'Panduan dihapus.');
    }
}
