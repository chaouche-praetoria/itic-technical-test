<?php

namespace App\Http\Controllers;

use App\Models\EvaluationAttempt;
use App\Services\EvaluationScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EvaluationAttemptController extends Controller
{
    public function __construct(
        private EvaluationScoringService $scoring,
    ) {}

    public function start(string $token)
    {
        $attempt = EvaluationAttempt::where('token', $token)
            ->with(['evaluation.questions.choices', 'evaluation.classroom', 'student', 'answers'])
            ->firstOrFail();

        // Auto-submit if the timer ran out while the page was closed.
        if ($attempt->status === 'in_progress' && $attempt->isTimedOut()) {
            $this->finalize($attempt, expired: true);
        }

        if (in_array($attempt->status, ['completed', 'expired', 'pending_review'], true)) {
            return Inertia::render('Eval/Completed', [
                'evaluation' => ['title' => $attempt->evaluation->title],
                'student' => ['name' => $attempt->student->full_name],
                'status' => $attempt->status,
                'score' => $attempt->score,
                'points_earned' => $attempt->points_earned,
                'points_total' => $attempt->points_total,
            ]);
        }

        $evaluation = $attempt->evaluation;

        if ($attempt->status === 'pending') {
            return Inertia::render('Eval/Welcome', [
                'token' => $attempt->token,
                'evaluation' => [
                    'title' => $evaluation->title,
                    'subject' => $evaluation->subject,
                    'time_limit_minutes' => $evaluation->time_limit_minutes,
                    'questions_count' => $evaluation->questions->count(),
                    'total_points' => (int) $evaluation->questions->sum('points'),
                ],
                'student' => ['name' => $attempt->student->full_name],
            ]);
        }

        // in_progress — render the exam
        $existingAnswers = $attempt->answers->keyBy('evaluation_question_id')->map(fn ($a) => [
            'answer_text' => $a->answer_text,
            'selected_choice_ids' => $a->selected_choice_ids ?? [],
        ]);

        return Inertia::render('Eval/Take', [
            'token' => $attempt->token,
            'evaluation' => [
                'title' => $evaluation->title,
                'subject' => $evaluation->subject,
                'statement' => $evaluation->statement,
                'attachment_path' => $evaluation->attachment_path,
                'attachment_type' => $evaluation->attachment_type,
                'time_limit_minutes' => $evaluation->time_limit_minutes,
            ],
            'expires_at' => $attempt->expires_at->toIso8601String(),
            'questions' => $evaluation->questions->map(fn ($q) => [
                'id' => $q->id,
                'type' => $q->type,
                'statement' => $q->statement,
                'points' => $q->points,
                'multiple_answers' => $q->multiple_answers,
                'choices' => $q->choices->map(fn ($c) => ['id' => $c->id, 'text' => $c->text]),
            ]),
            'existingAnswers' => $existingAnswers,
        ]);
    }

    public function begin(string $token)
    {
        $attempt = EvaluationAttempt::where('token', $token)
            ->where('status', 'pending')
            ->with('evaluation')
            ->firstOrFail();

        $attempt->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes($attempt->evaluation->time_limit_minutes),
        ]);

        return redirect()->route('eval.start', $token);
    }

    public function saveAnswer(Request $request, string $token)
    {
        $attempt = EvaluationAttempt::where('token', $token)
            ->where('status', 'in_progress')
            ->firstOrFail();

        // Reject saves after the deadline.
        if ($attempt->isTimedOut()) {
            $this->finalize($attempt, expired: true);
            return response()->json(['expired' => true], 422);
        }

        $validated = $request->validate([
            'question_id' => 'required|exists:evaluation_questions,id',
            'answer_text' => 'nullable|string',
            'selected_choice_ids' => 'nullable|array',
            'selected_choice_ids.*' => 'integer',
        ]);

        $attempt->answers()->updateOrCreate(
            ['evaluation_question_id' => $validated['question_id']],
            [
                'answer_text' => $validated['answer_text'] ?? null,
                'selected_choice_ids' => $validated['selected_choice_ids'] ?? null,
            ],
        );

        return response()->json(['success' => true]);
    }

    public function submit(string $token)
    {
        $attempt = EvaluationAttempt::where('token', $token)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $this->finalize($attempt, expired: $attempt->isTimedOut());

        return redirect()->route('eval.result', $token);
    }

    public function result(string $token)
    {
        $attempt = EvaluationAttempt::where('token', $token)
            ->with(['evaluation', 'student'])
            ->firstOrFail();

        return Inertia::render('Eval/Completed', [
            'evaluation' => ['title' => $attempt->evaluation->title],
            'student' => ['name' => $attempt->student->full_name],
            'status' => $attempt->status,
            'score' => $attempt->score,
            'points_earned' => $attempt->points_earned,
            'points_total' => $attempt->points_total,
        ]);
    }

    private function finalize(EvaluationAttempt $attempt, bool $expired = false): void
    {
        $attempt->update(['submitted_at' => now()]);
        $this->scoring->scoreAttempt($attempt);

        // scoreAttempt sets completed/pending_review; tag timed-out runs.
        if ($expired && $attempt->fresh()->status === 'completed') {
            $attempt->update(['status' => 'expired']);
        }
    }
}
