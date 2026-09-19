<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'nama', 'deskripsi', 'konten', 'file_path', 'created_by_user_id', 'is_restricted'])]
class LearningMaterial extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
