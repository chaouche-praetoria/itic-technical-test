<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'type', 'academic_level_id',
        'difficulty', 'statement', 'multiple_answers',
        'unit_tests', 'default_language', 'initial_code', 'is_active',
    ];

    protected $casts = [
        'multiple_answers' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function domains(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Domain::class);
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function themes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Theme::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class)->orderBy('order');
    }
}
