<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClassInvitation extends Model
{
    protected $fillable = [
        'classroom_id', 'email', 'token', 'status', 'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClassInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(40);
            }
        });
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'classroom_id');
    }
}
