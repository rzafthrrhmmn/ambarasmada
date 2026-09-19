<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(Request $request): Response
    {
        $pmtilesPath = 'storage/maps/sulsel_kontur.pmtiles';
        $pmtilesUrl = asset($pmtilesPath);
        $hasPmtiles = file_exists(public_path($pmtilesPath));

        $kabupatens = [
            ['id_kab' => '7301', 'nama_kab' => 'Kepulauan Selayar', 'bbox' => [120.30, -7.60, 121.30, -5.70]],
            ['id_kab' => '7302', 'nama_kab' => 'Bulukumba', 'bbox' => [120.00, -5.60, 120.50, -5.20]],
            ['id_kab' => '7303', 'nama_kab' => 'Bantaeng', 'bbox' => [119.85, -5.65, 120.10, -5.40]],
            ['id_kab' => '7304', 'nama_kab' => 'Jeneponto', 'bbox' => [119.50, -5.70, 119.95, -5.45]],
            ['id_kab' => '7305', 'nama_kab' => 'Takalar', 'bbox' => [119.30, -5.60, 119.65, -5.20]],
            ['id_kab' => '7306', 'nama_kab' => 'Gowa', 'bbox' => [119.38, -5.45, 120.08, -5.11]],
            ['id_kab' => '7307', 'nama_kab' => 'Sinjai', 'bbox' => [119.80, -5.35, 120.35, -5.10]],
            ['id_kab' => '7308', 'nama_kab' => 'Bone', 'bbox' => [119.85, -5.05, 120.45, -4.30]],
            ['id_kab' => '7309', 'nama_kab' => 'Maros', 'bbox' => [119.43, -5.15, 119.87, -4.72]],
            ['id_kab' => '7310', 'nama_kab' => 'Pangkajene Dan Kepulauan', 'bbox' => [119.35, -4.92, 119.85, -4.60]],
            ['id_kab' => '7311', 'nama_kab' => 'Barru', 'bbox' => [119.55, -4.65, 119.85, -4.10]],
            ['id_kab' => '7312', 'nama_kab' => 'Soppeng', 'bbox' => [119.70, -4.55, 120.05, -4.15]],
            ['id_kab' => '7313', 'nama_kab' => 'Wajo', 'bbox' => [119.90, -4.20, 120.45, -3.70]],
            ['id_kab' => '7314', 'nama_kab' => 'Sidenreng Rappang', 'bbox' => [119.70, -4.00, 120.15, -3.65]],
            ['id_kab' => '7315', 'nama_kab' => 'Pinrang', 'bbox' => [119.45, -3.90, 119.85, -3.40]],
            ['id_kab' => '7316', 'nama_kab' => 'Enrekang', 'bbox' => [119.63, -3.62, 120.05, -3.22]],
            ['id_kab' => '7317', 'nama_kab' => 'Luwu', 'bbox' => [120.00, -3.60, 120.45, -2.90]],
            ['id_kab' => '7318', 'nama_kab' => 'Tana Toraja', 'bbox' => [119.60, -3.30, 120.00, -2.90]],
            ['id_kab' => '7322', 'nama_kab' => 'Luwu Utara', 'bbox' => [119.85, -2.85, 120.70, -2.25]],
            ['id_kab' => '7324', 'nama_kab' => 'Luwu Timur', 'bbox' => [120.70, -2.90, 121.75, -2.25]],
            ['id_kab' => '7326', 'nama_kab' => 'Toraja Utara', 'bbox' => [119.72, -3.08, 120.15, -2.78]],
            ['id_kab' => '7371', 'nama_kab' => 'Kota Makassar', 'bbox' => [119.35, -5.25, 119.55, -5.05]],
            ['id_kab' => '7372', 'nama_kab' => 'Kota Parepare', 'bbox' => [119.60, -4.05, 119.70, -3.95]],
            ['id_kab' => '7373', 'nama_kab' => 'Kota Palopo', 'bbox' => [120.05, -3.10, 120.25, -2.90]],
        ];

        return Inertia::render('Peta/MapDenganPencarian', [
            'mapConfig' => [
                'pmtilesUrl' => $pmtilesUrl,
                'hasPmtiles' => $hasPmtiles,
                'center' => [120.2, -3.3],
                'zoom' => 10,
                'boundingBox' => [
                    'west' => 118.9,
                    'east' => 121.8,
                    'south' => -7.7,
                    'north' => -1.8,
                ],
            ],
            'kabupatens' => $kabupatens,
        ]);
    }
}
