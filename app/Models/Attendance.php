<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_session_id', 'member_id', 'keterangan', 'catatan', 'checked_at'])]
class Attendance extends Model
{
    protected $fillable = ['attendance_session_id', 'member_id', 'keterangan', 'catatan', 'checked_at'];

    /**
     * checked_at harus jadi objek tanggal, bukan string, supaya bisa diformat
     * dengan toIso8601String() saat dikirim ke Inertia. Tanpa cast ini halaman
     * kehadiran anggota gagal dengan Call to a member function on string.
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
