<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = ['webhook_id', 'payload', 'status_code', 'response', 'success'];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
