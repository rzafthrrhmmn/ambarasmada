<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'nama', 'lirik', 'file_path', 'created_by_user_id'])]
class AmbalanMedia extends Model
{
    use SoftDeletes;

    protected $table = 'ambalan_medias';

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
