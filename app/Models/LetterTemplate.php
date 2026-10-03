<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'file_path', 'description', 'placeholders', 'created_by_user_id'])]
class LetterTemplate extends Model
{
    protected $fillable = ['name', 'file_path', 'description', 'placeholders', 'created_by_user_id'];

    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
        ];
    }

    /**
     * Penanda yang terdeteksi waktu unggah, dihitung ulang bila kosong.
     *
     * @return array<int, string>
     */
    public function placeholderList(): array
    {
        return $this->placeholders ?? [];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
