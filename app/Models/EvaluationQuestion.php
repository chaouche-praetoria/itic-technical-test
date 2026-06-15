<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationQuestion extends Model
{
    protected $fillable = [
        'evaluation_id', 'type', 'statement', 'image_path',
        'multiple_answers', 'points', 'order',
    ];

    protected $casts = [
        'multiple_answers' => 'boolean',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(EvaluationQuestionChoice::class)->orderBy('order');
    }
}
