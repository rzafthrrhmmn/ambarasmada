<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        $user = $request->user();

        /*
         * Penentuan siapa boleh menyunting atau menghapus setiap kartu
         * ditentukan di sini, bukan dari daftar peran yang ditulis ulang di
         * Vue. Kalau aturan update dan destroy di bawah berubah, tampilan ikut
         * berubah tanpa harus menyentuh halaman.
         */
        $canManageAll = in_array($user->role, ['Admin', 'Pembina'], true);

        $articles = $query->paginate(15)->withQueryString();
        $articles->getCollection()->transform(fn (Article $article) => $article->setAttribute('can_edit', $article->author_id === $user->id || $canManageAll));
        $articles->getCollection()->transform(fn (Article $article) => $article->setAttribute('can_delete', $canManageAll));

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
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
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
                Storage::disk('public')->delete($article->image);
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

    public function show(Request $request, Article $article): Response
    {
        // Daftar hanya menampilkan artikel yang sudah terbit, jadi artikel
        // draf yang bocor lewat URL harus ditolak di sini juga. Penulisnya
        // sendiri tetap boleh membuka drafnya untuk menyunting.
        $isPrivileged = $article->author_id === $request->user()->id
            || in_array($request->user()->role, ['Admin', 'Pembina'], true);

        abort_unless($article->is_published || $isPrivileged, 404);

        $article->load(['ambalan', 'author']);

        return Inertia::render('Articles/Show', [
            'article' => $article,
            // Halaman detail ikut menentukan apakah tombol sunting dan hapus
            // ditampilkan. Daftar peran di sini persis sama dengan yang dipakai
            // update dan destroy, jadi halaman ini tidak perlu menyimpan daftar
            // sendiri yang bisa melenceng dari server.
            'canManage' => $isPrivileged,
            'canDelete' => in_array($request->user()->role, ['Admin', 'Pembina'], true),
        ]);
    }
}
