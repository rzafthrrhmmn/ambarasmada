<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ambalan_id', 'created_by', 'judul', 'agenda', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'lokasi', 'jenis', 'status'])]
class Meeting extends Model
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

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(MeetingAgenda::class);
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(MeetingMinute::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MeetingVote::class);
    }
}
