<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'domain_id', 'academic_level_id',
        'duration_minutes', 'question_timer', 'question_time_seconds',
        'single_attempt', 'link_expiry_hours', 'is_active',
    ];

    protected $casts = [
        'question_timer' => 'boolean',
        'single_attempt' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TestTemplateRule::class);
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }
}
