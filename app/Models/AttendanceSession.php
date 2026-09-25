<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['ambalan_id', 'nama', 'tanggal', 'lokasi', 'latitude', 'longitude', 'radius', 'materi_path', 'materi_nama', 'materi_mime_type', 'materi_size', 'qr_token', 'qr_dynamic', 'qr_refreshed_at', 'created_by'])]
class AttendanceSession extends Model
{
    protected $fillable = ['ambalan_id', 'nama', 'tanggal', 'lokasi', 'latitude', 'longitude', 'radius', 'materi_path', 'materi_nama', 'materi_mime_type', 'materi_size', 'qr_token', 'qr_dynamic', 'qr_refreshed_at', 'created_by'];

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'qr_dynamic' => 'boolean',
            'qr_refreshed_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius' => 'integer',
        ];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function refreshQrToken(): void
    {
        $this->update([
            'qr_token' => Str::upper(Str::random(12)),
            'qr_refreshed_at' => now(),
        ]);
    }
}
