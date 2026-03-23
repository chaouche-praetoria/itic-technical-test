<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestTemplateRule extends Model
{
    protected $fillable = [
        'test_template_id', 'theme_id', 'question_type', 'difficulty', 'count',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TestTemplate::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
