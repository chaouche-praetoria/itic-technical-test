<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'test_session_id', 'url', 'event', 'payload',
        'response_status', 'response_body', 'success', 'attempt',
    ];

    protected $casts = [
        'payload' => 'json',
        'success' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }
}
