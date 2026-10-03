<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk Penyimpanan Berkas Surat
    |--------------------------------------------------------------------------
    |
    | Template .docx dan lampiran surat tidak disimpan di disk "public"
    | secara langsung karena folder aplikasi hanya-baca di hosting Vercel.
    | Isi LETTERS_DISK dengan nama disk yang tersedia, misalnya "s3".
    |
    | Kalau LETTERS_DISK tidak diisi, memakai FILESYSTEM_DISK, dan kalau
    | itu juga kosong memakai "public" untuk pengembangan lokal.
    |
    */

    'disk' => env('LETTERS_DISK') ?: (env('FILESYSTEM_DISK') ?: 'public'),

    /*
    |--------------------------------------------------------------------------
    | Template
    |--------------------------------------------------------------------------
    */

    'templates_directory' => 'letter-templates',

    'attachments_directory' => 'letters',

    /*
    |--------------------------------------------------------------------------
    | Batas Berkas
    |--------------------------------------------------------------------------
    */

    'max_template_kilobytes' => 10240,

];
