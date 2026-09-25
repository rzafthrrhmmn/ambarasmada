<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['ambalan_id', 'uploaded_by', 'judul', 'deskripsi', 'image', 'kategori'])]
class Gallery extends Model
{
    protected $fillable = ['ambalan_id', 'uploaded_by', 'judul', 'deskripsi', 'image', 'kategori'];

    protected $casts = [
        'kategori' => 'string',
    ];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

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
