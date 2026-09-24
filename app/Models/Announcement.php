<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['ambalan_id', 'judul', 'isi', 'image', 'published_at', 'created_by'])]
class Announcement extends Model
{
    use SoftDeletes;

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
