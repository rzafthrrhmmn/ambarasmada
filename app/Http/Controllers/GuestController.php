<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Announcement;
use App\Models\Gallery;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $ambalan = Ambalan::first();
        $hasKategoriColumn = Schema::hasColumn('announcements', 'kategori');

        $announcements = Announcement::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'judul', 'isi', 'published_at', 'image'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'isi' => $item->isi,
                'published_at' => $item->published_at,
                'image' => $item->image,
                'image_url' => $item->image_url,
                'kategori' => $hasKategoriColumn ? ($item->kategori ?? null) : null,
            ]);

        $sliderAnnouncements = Announcement::whereNotNull('published_at')
            ->whereNotNull('image')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'judul', 'isi', 'image'])
            ->map(fn ($item) => [
                'src' => $item->image_url,
                'title' => $item->judul,
                'description' => Str::limit(strip_tags($item->isi), 100),
                'alt' => $item->judul,
            ]);

        $gallery = Gallery::whereNotNull('image')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(fn ($item) => [
                'src' => $item->image_url,
                'title' => $item->judul,
                'description' => $item->deskripsi,
                'kategori' => $item->kategori,
            ]);

        return Inertia::render('Guest/Home', [
            'ambalan' => $ambalan ? [
                'id' => $ambalan->id,
                'nama' => $ambalan->nama,
                'kode' => $ambalan->kode,
                'logo_path' => $ambalan->logo_path,
                'logo_url' => $ambalan->logo_url,
            ] : null,
            'announcements' => $announcements,
            'sliderSlides' => $sliderAnnouncements->isNotEmpty() ? $sliderAnnouncements : [],
            'gallery' => $gallery->isNotEmpty() ? $gallery : [],
            'stats' => [
                'members' => Member::where('status_aktif', 'Aktif')->count(),
                'alumni' => Member::where('status_aktif', 'Alumni')->count(),
            ],
        ]);
    }
}
