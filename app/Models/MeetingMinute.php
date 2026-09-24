<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meeting_id', 'created_by', 'notulen', 'keputusan', 'tindak_lanjut'])]
class MeetingMinute extends Model
{
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
