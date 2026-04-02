<?php

namespace App\Services;

use App\Models\TestSession;
use App\Models\Question;

class TestScoringService
{
    public function calculateSessionScores(TestSession $session): void
    {
        $session->load(['sessionQuestions.question.choices', 'answers']);
        
        $totalScore = 0;
        $correctCount = 0;
        $totalQuestions = $session->sessionQuestions->count();
        $totalPointsEarned = 0;
        $totalPointsPossible = 0;

        foreach ($session->sessionQuestions as $sq) {
            $question = $sq->question;
            $maxPts = $this->maxPoints($question);
            $totalPointsPossible += $maxPts;

            $answerRecord = $session->answers->firstWhere('question_id', $question->id);

            if (!$answerRecord) {
                continue;
            }

            $score = $answerRecord->score ?? 0;

            // For MCQ, we re-verify if not already set or for consistency
            if ($question->type === 'mcq') {
                $score = $this->scoreMcq($question, $answerRecord->answer);
                $answerRecord->update(['score' => $score]);
            }
            // For Code, score is stored during execution
            // For Text, score is updated via manual grading

            if ($score >= 100) {
                $correctCount++;
            }
            $totalScore += $score;
            $totalPointsEarned += (int) round(($score / 100) * $maxPts);
        }

        $finalScore = $totalQuestions > 0 ? round($totalScore / $totalQuestions, 2) : 0;

        $session->update([
            'score' => $finalScore,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctCount,
            'points_earned' => $totalPointsEarned,
            'points_total' => $totalPointsPossible,
        ]);
    }

    public function maxPoints(Question $question): int
    {
        return match(true) {
            $question->type === 'mcq'  && $question->difficulty === 'easy'   => 10,
            $question->type === 'mcq'  && $question->difficulty === 'medium' => 20,
            $question->type === 'mcq'  && $question->difficulty === 'hard'   => 30,
            $question->type === 'text' && $question->difficulty === 'easy'   => 20,
            $question->type === 'text' && $question->difficulty === 'medium' => 30,
            $question->type === 'text' && $question->difficulty === 'hard'   => 40,
            $question->type === 'code' && $question->difficulty === 'easy'   => 20,
            $question->type === 'code' && $question->difficulty === 'medium' => 40,
            $question->type === 'code' && $question->difficulty === 'hard'   => 60,
            default => 10,
        };
    }

    public function scoreMcq(Question $question, $answer): float
    {
        if (!$answer) {
            return 0;
        }

        $correctIds = $question->choices->where('is_correct', true)->pluck('id')->sort()->values();
        $givenIds = collect(is_array($answer) ? $answer : [$answer])->sort()->values();

        return $correctIds->toArray() === $givenIds->toArray() ? 100.0 : 0.0;
    }
}
