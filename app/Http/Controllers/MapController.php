<?php

namespace App\Http\Controllers;

use App\Support\WilayahSulawesiSelatan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Peta/MapDenganPencarian', [
            'mapConfig' => array_merge($this->assetConfig(), [
                'center' => [120.2, -3.3],
                'zoom' => 10,
                'boundingBox' => [
                    'west' => 118.9,
                    'east' => 121.8,
                    'south' => -7.7,
                    'north' => -1.8,
                ],
            ]),
            'kabupatens' => WilayahSulawesiSelatan::kabupatens(),
        ]);
    }

    public function kontur(Request $request): Response
    {
        // Peta kontur tidak punya pemilih wilayah, jadi daftar kabupaten dan
        // kecamatan tidak dikirim ke halaman ini. Sebelumnya daftar kabupaten
        // tetap terkirim walau tidak pernah dibaca, dan setelah kecamatan ikut
        // masuk daftarnya payload halaman ini jadi jauh lebih besar tanpa
        // bertambah manfaat.
        return Inertia::render('Peta/Index', [
            'mapConfig' => array_merge($this->assetConfig(), [
                // Titik tengah arsip kontur. Nilai lama [119.863, -0.900]
                // berlatang -0.9, itu di utara Sulawesi dan di luar jangkauan
                // arsip yang hanya membentang sampai -2.25. Peta karena itu
                // terbuka di laut kosong tanpa satu pun garis kontur.
                'center' => [119.586, -3.305],
                'zoom' => 10,
                'boundingBox' => [
                    'west' => 118.5,
                    'east' => 125.5,
                    'south' => -6.0,
                    'north' => 2.0,
                ],
            ]),
        ]);
    }

    /**
     * URL sumber peta untuk frontend.
     *
     * PMTiles di-host di Supabase Storage, jadi frontend memakai URL absolut
     * dari PMTILES_URL. Berkas lokal hanya dipakai sebagai cadangan saat
     * variabel itu kosong, misalnya di pengembangan.
     */
    private function assetConfig(): array
    {
        $remotePmtilesUrl = config('map.pmtiles_url');
        $pmtilesPath = config('map.pmtiles_path');

        $geojsonPath = config('map.geojson_path');
        $kecamatanGeojsonPath = config('map.kecamatan_geojson_path');

        return [
            'pmtilesUrl' => filled($remotePmtilesUrl) ? $remotePmtilesUrl : asset($pmtilesPath),
            'geojsonUrl' => asset($geojsonPath),
            'hasPmtiles' => filled($remotePmtilesUrl) || file_exists(public_path($pmtilesPath)),
            'hasGeojson' => file_exists(public_path($geojsonPath)),
            // Batas kecamatan hanya dipakai untuk menyorot kecamatan terpilih.
            // Berkasnya opsional karena sebagian kecamatan belum punya geometri
            // dan halaman peta harus tetap jalan saat berkasnya tidak ada.
            'kecamatanGeojsonUrl' => asset($kecamatanGeojsonPath),
            'hasKecamatanGeojson' => file_exists(public_path($kecamatanGeojsonPath)),
        ];
    }
}
