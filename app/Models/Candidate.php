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
        'date_test_technique'
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }
}
