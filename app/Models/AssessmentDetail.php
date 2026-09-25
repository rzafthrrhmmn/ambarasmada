<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'kategori', 'deskripsi', 'nilai'])]
class AssessmentDetail extends Model
{
    protected $fillable = ['assessment_id', 'kategori', 'deskripsi', 'nilai'];

    protected $casts = [
        'nilai' => 'decimal:2',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
