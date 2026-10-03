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

    /*
    |--------------------------------------------------------------------------
    | Batas Kecamatan
    |--------------------------------------------------------------------------
    |
    | Batas kecamatan dipakai untuk menyorot kecamatan yang dipilih. Berkas ini
    | jauh lebih besar daripada batas kabupaten karena detail batasnya jauh
    | lebih rapat, jadi frontend memperlakukannya sebagai opsional: layer hanya
    | dibuat kalau berkasnya ada, dan halaman peta tetap bisa dibuka tanpa
    | berkas ini.
    |
    */

    'kecamatan_geojson_path' => 'storage/maps/batas_kecamatan_sulsel.geojson',

];
