<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['sku_submission_id', 'file_path', 'tipe', 'caption'])]
class SkuSubmissionMedia extends Model
{
    public function submission(): BelongsTo
    {
        return $this->belongsTo(SkuSubmission::class, 'sku_submission_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function isPhoto(): bool
    {
        return $this->tipe === 'photo';
    }

    public function isImage(): bool
    {
        return in_array(
            strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            true
        );
    }
}
