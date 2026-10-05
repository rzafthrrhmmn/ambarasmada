<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['ambalan_id', 'author_id', 'judul', 'konten', 'kategori', 'image', 'is_published'])]
class Article extends Model
{
    protected $fillable = ['ambalan_id', 'author_id', 'judul', 'konten', 'kategori', 'image', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * URL gambar sampul.
     *
     * Halaman daftar dan detail sudah membaca `image_url`, jadi tanpa accessor
     * ini gambar yang diunggah penulis diam-diam tidak pernah tampil. Aturannya
     * disamakan dengan Gallery: berkas di Vercel tidak persisten antar
     * deployment, jadi URL-nya sengaja dikosongkan daripada menautkan gambar
     * yang pasti rusak.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (getenv('VERCEL') === '1') {
            return null;
        }

        try {
            return Storage::disk('public')->url($this->image);
        } catch (\Throwable) {
            return null;
        }
    }
}
