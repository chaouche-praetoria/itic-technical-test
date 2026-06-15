<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationAttempt extends Model
{
    protected $fillable = [
        'evaluation_id', 'student_id', 'token', 'status',
        'started_at', 'expires_at', 'submitted_at',
        'score', 'points_earned', 'points_total',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class);
    }

    public function isTimedOut(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
