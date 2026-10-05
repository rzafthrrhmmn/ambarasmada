<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ambalan;
use App\Models\Announcement;
use App\Models\Gallery;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Response;

class GuestController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $cached = Cache::tags(['guest'])->remember('guest.home.api', 300, function () {
            $ambalan = Ambalan::first();

            $announcements = Announcement::whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(['id', 'judul', 'isi', 'published_at', 'kategori'])
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'isi' => Str::limit(strip_tags($item->isi), 150),
                    'published_at' => $item->published_at?->toIso8601String(),
                    'kategori' => $item->kategori,
                ]);

            $sliderAnnouncements = Announcement::whereNotNull('published_at')
                ->whereNotNull('image')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get()
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

            return [
                'ambalan' => $ambalan ? [
                    'id' => $ambalan->id,
                    'nama' => $ambalan->nama,
                    'kode' => $ambalan->kode,
                    'logo_url' => $ambalan->logo_url,
                ] : null,
                'announcements' => $announcements,
                'sliderSlides' => $sliderAnnouncements->isNotEmpty() ? $sliderAnnouncements : [],
                'gallery' => $gallery->isNotEmpty() ? $gallery : [],
                'stats' => [
                    'members' => Member::where('status_aktif', 'Aktif')->count(),
                    'alumni' => Member::where('status_aktif', 'Alumni')->count(),
                ],
            ];
        });

        return response()->json($cached, 200, ['Cache-Control' => 'public, max-age=300, stale-while-revalidate=60']);
    }
}
