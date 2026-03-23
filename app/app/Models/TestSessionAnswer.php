<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSessionAnswer extends Model
{
    protected $fillable = [
        'test_session_id', 'question_id', 'answer',
        'score', 'execution_result', 'time_spent_seconds',
    ];

    protected $casts = [
        'answer' => 'json',
        'execution_result' => 'json',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
