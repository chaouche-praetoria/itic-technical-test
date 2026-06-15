<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'classroom_id', 'first_name', 'last_name', 'email', 'phone',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'classroom_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(EvaluationAttempt::class);
    }
}
