<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicLevel extends Model
{
    protected $fillable = ['name', 'slug', 'order', 'fallback_level_id'];
    
    public function fallbackLevel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'fallback_level_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function testTemplates(): HasMany
    {
        return $this->hasMany(TestTemplate::class);
    }
}
