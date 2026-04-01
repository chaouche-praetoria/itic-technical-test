<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = ['name', 'slug', 'domain_id'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function templateRules(): HasMany
    {
        return $this->hasMany(TestTemplateRule::class);
    }
}
