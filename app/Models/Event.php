<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ambalan_id', 'created_by', 'nama', 'deskripsi', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'lokasi', 'image', 'jenis', 'status'])]
class Event extends Model
{
    protected $casts = [
        'tanggal' => 'date',
        'waktu_mulai' => 'time',
        'waktu_selesai' => 'time',
    ];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }
}
