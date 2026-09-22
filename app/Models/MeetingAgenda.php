<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meeting_id', 'judul', 'deskripsi', 'urutan'])]
class MeetingAgenda extends Model
{
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MeetingVote::class);
    }
}
