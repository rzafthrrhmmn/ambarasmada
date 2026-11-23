<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'nama', 'kode', 'deskripsi', 'kepala_sanga_id'])]
class Sangga extends Model
{
    use SoftDeletes;

    protected $fillable = ['ambalan_id', 'nama', 'kode', 'deskripsi', 'kepala_sanga_id'];

    protected function casts(): array
    {
        return [];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function kepalaSanga(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'kepala_sanga_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'sanga_id')->orderBy('nama_lengkap');
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }
}
