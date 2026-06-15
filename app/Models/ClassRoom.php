<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClassRoom extends Model
{
    protected $table = 'classrooms';

    protected $fillable = [
        'name', 'description', 'academic_level_id', 'user_id', 'join_token', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClassRoom $classroom) {
            if (empty($classroom->join_token)) {
                $classroom->join_token = Str::random(40);
            }
        });
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ClassInvitation::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function joinUrl(): string
    {
        return route('class.join', $this->join_token);
    }
}
