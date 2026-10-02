<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Peta Kontur (PMTiles)
    |--------------------------------------------------------------------------
    |
    | Arsip PMTiles Sulawesi Selatan tidak dikirim ke server Vercel karena
    | .vercelignore mengecualikan *.pmtiles. File-nya di-host di Supabase
    | Storage dan dibaca langsung oleh browser lewat HTTP Range, jadi frontend
    | membutuhkan URL absolut, bukan path relatif.
    |
    | Kosongkan PMTILES_URL untuk kembali memakai berkas lokal di public/.
    |

    */

    'pmtiles_url' => env('PMTILES_URL'),

    'pmtiles_path' => 'storage/maps/sulsel_kontur.pmtiles',

    'geojson_path' => 'storage/maps/batas_kabupaten_sulsel.geojson',

];
