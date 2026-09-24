<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nama', 'kode', 'alamat', 'status', 'logo_path'])]
class Ambalan extends Model
{
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (getenv('VERCEL') === '1') {
            return null;
        }

        try {
            return Storage::disk('public')->url($this->logo_path);
        } catch (\Throwable) {
            return null;
        }
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class);
    }

    public function financeCategories(): HasMany
    {
        return $this->hasMany(FinanceCategory::class);
    }

    public function financePeriods(): HasMany
    {
        return $this->hasMany(FinancePeriod::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }
}
