<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['created_by', 'user_id', 'judul', 'deskripsi', 'jadwal', 'jenis', 'is_sent'])]
class Reminder extends Model
{
    protected $casts = [
        'jadwal' => 'datetime',
        'is_sent' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
