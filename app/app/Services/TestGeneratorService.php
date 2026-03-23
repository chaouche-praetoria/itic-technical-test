<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Question;
use App\Models\TestSession;
use App\Models\TestTemplate;
use Illuminate\Support\Str;

class TestGeneratorService
{
    public function generateSession(Candidate $candidate, TestTemplate $template): TestSession
    {
        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'test_template_id' => $template->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($template->link_expiry_hours),
            'status' => 'pending',
        ]);

        $order = 0;
        foreach ($template->rules as $rule) {
            $query = Question::where('theme_id', $rule->theme_id)
                ->where('type', $rule->question_type)
                ->where('is_active', true);

            if ($rule->difficulty) {
                $query->where('difficulty', $rule->difficulty);
            }

            $questions = $query->inRandomOrder()->limit($rule->count)->get();

            foreach ($questions as $question) {
                $session->sessionQuestions()->create([
                    'question_id' => $question->id,
                    'order' => $order++,
                ]);
            }
        }

        return $session;
    }
}
