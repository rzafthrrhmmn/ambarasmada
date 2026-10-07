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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        try {
            $useCache = Cache::getDefaultDriver() !== 'array' && Cache::getDefaultDriver() !== 'null';

            if ($useCache) {
                try {
                    $cached = Cache::remember('guest.home.api', 300, function () {
                        return $this->buildGuestHomeData();
                    });

                    return response()->json($cached, 200, ['Cache-Control' => 'public, max-age=300, stale-while-revalidate=60']);
                } catch (\Throwable $cacheException) {
                    $cached = $this->buildGuestHomeData();

                    return response()->json($cached, 200, ['Cache-Control' => 'no-cache']);
                }
            }

            $cached = $this->buildGuestHomeData();

            return response()->json($cached, 200, ['Cache-Control' => 'no-cache']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function newsletter(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Periksa kembali alamat email Anda.',
                'errors' => $e->validator->errors(),
            ], 422);
        }

        Log::info('Guest newsletter subscription', [
            'email' => $validated['email'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email Anda berhasil terdaftar. Terima kasih telah berlangganan.',
        ]);
    }

    private function buildGuestHomeData(): array
    {
        $ambalan = Ambalan::first();

        $hasKategoriColumn = Schema::hasColumn('announcements', 'kategori');

        $announcements = Announcement::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'judul', 'isi', 'published_at'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'isi' => Str::limit(strip_tags($item->isi), 150),
                'published_at' => $item->published_at?->toIso8601String(),
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
            ])
            ->filter(fn ($slide) => ! empty($slide['src']));

        $gallery = Gallery::whereNotNull('image')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(fn ($item) => [
                'src' => $item->image_url,
                'title' => $item->judul,
                'description' => $item->deskripsi,
                'kategori' => $item->kategori,
            ])
            ->filter(fn ($item) => ! empty($item['src']));

        return [
            'ambalan' => $ambalan ? [
                'id' => $ambalan->id,
                'nama' => $ambalan->nama,
                'kode' => $ambalan->kode,
                'logo_url' => $ambalan->logo_url ?: asset('images/Logo_Ambalan.png'),
            ] : null,
            'announcements' => $announcements,
            'sliderSlides' => $sliderAnnouncements->isNotEmpty() ? $sliderAnnouncements : [],
            'gallery' => $gallery->isNotEmpty() ? $gallery : [],
            'stats' => [
                'members' => Member::where('status_aktif', 'Aktif')->count(),
                'alumni' => Member::where('status_aktif', 'Alumni')->count(),
            ],
        ];
    }
}
