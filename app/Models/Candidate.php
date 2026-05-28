<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'hubspot_id',
        'formation_souhaitee',
        'formation_souhaitee_pour_ypareo',
        'score_test_technique',
        'resultat_test_technique',
        'date_test_technique',
        'added_by'
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    public function latestSession(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TestSession::class)->latestOfMany();
    }

    public function updateScoreFromSessions(): void
    {
        $session = $this->testSessions()
            ->whereIn('status', ['completed', 'pending_review'])
            ->whereNotNull('score')
            ->latest()
            ->first();

        if ($session) {
            $scoreStr = number_format($session->score, 2);
            $resultLabel = $session->status === 'completed'
                ? ($session->score >= 70 ? 'admis' : 'Echec - A requalifier')
                : 'En cours de correction';

            $this->update([
                'score_test_technique' => $scoreStr,
                'resultat_test_technique' => $resultLabel,
                'date_test_technique' => $session->completed_at ? $session->completed_at->format('Y-m-d') : now()->format('Y-m-d'),
            ]);
        }
    }
}

