<?php

namespace App\Services;

use App\Models\EvaluationAttempt;
use App\Models\EvaluationQuestion;

class EvaluationScoringService
{
    /**
     * (Re)compute the score of an attempt.
     *
     * MCQ questions are auto-graded (full points on an exact match of the
     * correct choices, 0 otherwise). Text questions keep the manual
     * `points_awarded` set by the teacher; while ungraded they are null and
     * the attempt stays in `pending_review`.
     */
    public function scoreAttempt(EvaluationAttempt $attempt): void
    {
        $attempt->load([
            'evaluation.questions.choices',
            'answers',
        ]);

        $pointsTotal = 0;
        $pointsEarned = 0;
        $hasUngradedText = false;

        foreach ($attempt->evaluation->questions as $question) {
            $pointsTotal += (int) $question->points;

            $answer = $attempt->answers->firstWhere('evaluation_question_id', $question->id);

            if ($question->type === 'mcq') {
                $awarded = $this->scoreMcq($question, $answer?->selected_choice_ids ?? []);
                if ($answer) {
                    $answer->update([
                        'points_awarded' => $awarded,
                        'is_correct' => $awarded === (int) $question->points,
                    ]);
                }
                $pointsEarned += $awarded;
                continue;
            }

            // text question — manual grading
            if ($answer && $answer->points_awarded !== null) {
                $pointsEarned += (int) $answer->points_awarded;
            } elseif (trim((string) ($answer->answer_text ?? '')) !== '') {
                $hasUngradedText = true;
            }
        }

        $score = $pointsTotal > 0 ? round(($pointsEarned / $pointsTotal) * 100, 2) : 0;

        $attempt->update([
            'points_total' => $pointsTotal,
            'points_earned' => $pointsEarned,
            'score' => $score,
            'status' => $hasUngradedText ? 'pending_review' : 'completed',
        ]);
    }

    /**
     * Full points on an exact match between selected and correct choices.
     */
    public function scoreMcq(EvaluationQuestion $question, array $selectedIds): int
    {
        if (empty($selectedIds)) {
            return 0;
        }

        $correctIds = $question->choices->where('is_correct', true)->pluck('id')->sort()->values()->all();
        $givenIds = collect($selectedIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        return $correctIds === $givenIds ? (int) $question->points : 0;
    }
}
