<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'domain_id',
        'duration_minutes', 'question_timer', 'question_time_seconds',
        'single_attempt', 'link_expiry_hours', 'is_active', 'ypareo_codes',
    ];

    protected $casts = [
        'question_timer' => 'boolean',
        'single_attempt' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function academicLevels(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(AcademicLevel::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TestTemplateRule::class);
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    /**
     * Find an active test template that matches the given Ypareo code.
     * Supports comma-separated lists of codes (case-insensitive and trimmed).
     * 
     * @param string $code
     * @return self|null
     */
    public static function findByYpareoCode(string $code): ?self
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return self::where('is_active', true)
            ->get()
            ->first(function (TestTemplate $template) use ($code) {
                if (empty($template->ypareo_codes)) {
                    return false;
                }
                $codes = array_map('trim', explode(',', strtolower($template->ypareo_codes)));
                return in_array(strtolower($code), $codes);
            });
    }
}
