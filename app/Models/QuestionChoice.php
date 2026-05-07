<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionChoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['question_id', 'text', 'is_correct', 'order', 'image_path'];

    protected $casts = ['is_correct' => 'boolean'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
