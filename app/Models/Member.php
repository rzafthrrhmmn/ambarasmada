<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'user_id', 'nta', 'angkatan', 'nomor_urut', 'nta_username', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'kelas', 'tingkatan', 'tahun_lulus', 'status_aktif', 'no_hp'])]
class Member extends Model
{
    use SoftDeletes;

    protected $appends = ['position_label'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $member): void {
            if ($member->angkatan === null) {
                $matches = [];
                $member->angkatan = preg_match('/^\d{1,22}\.(\d{3})\.(\d{3})$/', (string) $member->nta, $matches) === 1
                    ? $matches[1]
                    : '001';
            }

            if ($member->nomor_urut === null) {
                $matches = [];
                $member->nomor_urut = preg_match('/^\d{1,22}\.\d{3}\.(\d{3})$/', (string) $member->nta, $matches) === 1
                    ? (int) $matches[1]
                    : ((int) static::where('angkatan', $member->angkatan)->max('nomor_urut') + 1);
            }

            if ($member->nta_username === null) {
                $member->nta_username = $member->nta ?: sprintf(
                    '%s.%s.%03d',
                    config('app.gudep_prefix', '31082008'),
                    $member->angkatan,
                    $member->nomor_urut
                );
            }

            if ($member->nta === null) {
                $member->nta = $member->nta_username;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'tahun_lulus' => 'integer',
        ];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alumniProfile(): HasOne
    {
        return $this->hasOne(AlumniProfile::class);
    }

    public function skuSubmissions(): HasMany
    {
        return $this->hasMany(SkuSubmission::class);
    }

    public function tkkSubmissions(): HasMany
    {
        return $this->hasMany(TkkSubmission::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function inventoryLoans(): HasMany
    {
        return $this->hasMany(InventoryLoan::class);
    }

    public function memberPositions(): HasMany
    {
        return $this->hasMany(MemberPosition::class);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(MemberLogbook::class);
    }

    public function getPositionLabelAttribute(): string
    {
        $role = $this->user?->role;

        if ($role === 'Pembina') {
            return 'Pembina';
        }

        if ($role === 'Anggota') {
            return 'Anggota';
        }

        return $this->memberPositions->first()?->position?->name ?? 'Anggota Pengurus';
    }

    public function approvedSkuCount(): int
    {
        return $this->skuSubmissions()->where('status', 'Approved')->count();
    }
}
