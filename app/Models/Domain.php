<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class);
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    public function testTemplates(): HasMany
    {
        return $this->hasMany(TestTemplate::class);
    }
}
