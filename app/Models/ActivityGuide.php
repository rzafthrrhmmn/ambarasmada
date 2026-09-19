<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'kecamatan', 'nama', 'teks_susunan_upacara', 'checklist_perlengkapan', 'created_by_user_id'])]
class ActivityGuide extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'checklist_perlengkapan' => 'array',
        ];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
