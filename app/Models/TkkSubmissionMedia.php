<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['tkk_submission_id', 'file_path', 'tipe', 'caption'])]
class TkkSubmissionMedia extends Model
{
    public function submission(): BelongsTo
    {
        return $this->belongsTo(TkkSubmission::class, 'tkk_submission_id');
    }

    public function getUrlAttribute(): string
    {
        if (getenv('VERCEL') === '1') {
            return '';
        }

        try {
            return Storage::disk('public')->url($this->file_path);
        } catch (\Throwable) {
            return '';
        }
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
