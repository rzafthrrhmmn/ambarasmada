<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Article::query()
            ->with(['ambalan', 'author'])
            ->where('is_published', true)
            ->orderByDesc('created_at');

        if ($request->string('kategori')->isNotEmpty()) {
            $query->where('kategori', $request->string('kategori'));
        }

        $articles = $query->paginate(15)->withQueryString();
        $categories = ['Laporan Kegiatan', 'Artikel', 'Berita', 'Tips & Trik', 'Lainnya'];

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
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
            'image' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'is_published' => ['required', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('articles/gallery', 'public');
        }

        Article::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'author_id' => $request->user()->id,
        ]);

        return redirect()->route('articles.index')->with('success', 'Artikel berhasil dibuat.');
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Anggota'], true), 403);
        abort_unless($article->author_id === $request->user()->id || in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'kategori' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'is_published' => ['required', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($article->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($article->image);
            }
            $data['image'] = $request->file('image')->store('articles/gallery', 'public');
        }

        $article->update($data);

        return back()->with('success', 'Artikel diperbarui.');
    }

    public function destroy(Request $request, Article $article): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $article->delete();
        return redirect()->route('articles.index')->with('success', 'Artikel dihapus.');
    }

    public function show(Article $article): Response
    {
        $article->load(['ambalan', 'author']);
        return Inertia::render('Articles/Show', ['article' => $article]);
    }
}
