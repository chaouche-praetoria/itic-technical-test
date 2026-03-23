<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\TestSession;
use App\Models\TestSessionAnswer;
use App\Services\Judge0Service;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    public function __construct(
        private Judge0Service $judge0,
        private WebhookService $webhook
    ) {}

    public function start(string $token)
    {
        $session = TestSession::where('token', $token)
            ->with(['candidate', 'template', 'sessionQuestions.question.choices'])
            ->firstOrFail();

        if ($session->status === 'expired' || $session->expires_at->isPast()) {
            $session->update(['status' => 'expired']);
            return Inertia::render('Test/Expired');
        }

        if ($session->status === 'completed') {
            return Inertia::render('Test/Completed', [
                'score' => $session->score,
                'candidate' => $session->candidate->full_name,
                'points_earned' => $session->points_earned,
                'points_total' => $session->points_total,
            ]);
        }

        if ($session->template->single_attempt && $session->status === 'in_progress') {
            // Allow resuming in_progress
        }

        if ($session->status === 'pending') {
            $session->update(['status' => 'in_progress', 'started_at' => now(), 'ip_address' => request()->ip()]);
        }

        $questions = $session->sessionQuestions->map(fn($sq) => [
            'id' => $sq->question->id,
            'type' => $sq->question->type,
            'difficulty' => $sq->question->difficulty,
            'max_points' => $this->maxPoints($sq->question),
            'statement' => $sq->question->statement,
            'multiple_answers' => $sq->question->multiple_answers,
            'default_language' => $sq->question->default_language,
            'choices' => $sq->question->choices->map(fn($c) => ['id' => $c->id, 'text' => $c->text]),
            'order' => $sq->order,
        ]);

        return Inertia::render('Test/Take', [
            'session' => [
                'id' => $session->id,
                'token' => $session->token,
                'duration_minutes' => $session->template->duration_minutes,
                'question_timer' => $session->template->question_timer,
                'question_time_seconds' => $session->template->question_time_seconds,
                'started_at' => $session->started_at->toIso8601String(),
            ],
            'candidate' => ['name' => $session->candidate->full_name],
            'questions' => $questions,
        ]);
    }

    public function saveAnswer(Request $request, string $token)
    {
        $session = TestSession::where('token', $token)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable',
            'time_spent_seconds' => 'nullable|integer',
        ]);

        TestSessionAnswer::updateOrCreate(
            ['test_session_id' => $session->id, 'question_id' => $request->question_id],
            ['answer' => $request->answer, 'time_spent_seconds' => $request->time_spent_seconds]
        );

        return response()->json(['success' => true]);
    }

    public function executeCode(Request $request, string $token)
    {
        $session = TestSession::where('token', $token)->where('status', 'in_progress')->firstOrFail();

        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'code' => 'required|string',
            'language' => 'required|string',
        ]);

        $question = \App\Models\Question::findOrFail($request->question_id);
        $result = $this->judge0->execute($request->code, $request->language, $question->unit_tests ?? '');

        $score = 0;
        if (isset($result['passed'], $result['total']) && $result['total'] > 0) {
            $score = ($result['passed'] / $result['total']) * 100;
        }

        TestSessionAnswer::updateOrCreate(
            ['test_session_id' => $session->id, 'question_id' => $request->question_id],
            ['answer' => ['code' => $request->code, 'language' => $request->language], 'score' => $score, 'execution_result' => $result]
        );

        return response()->json($result);
    }

    public function submit(Request $request, string $token)
    {
        $session = TestSession::where('token', $token)
            ->where('status', 'in_progress')
            ->with(['sessionQuestions.question.choices', 'answers'])
            ->firstOrFail();

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

            $score = match ($question->type) {
                'mcq' => $this->scoreMcq($question, $answerRecord->answer),
                'code' => $answerRecord->score ?? 0,
                'text' => 0, // Manual scoring
                default => 0,
            };

            $answerRecord->update(['score' => $score]);

            if ($score >= 100) {
                $correctCount++;
            }
            $totalScore += $score;
            $totalPointsEarned += (int) round(($score / 100) * $maxPts);
        }

        $finalScore = $totalQuestions > 0 ? round($totalScore / $totalQuestions, 2) : 0;

        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($session->started_at),
            'score' => $finalScore,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctCount,
            'points_earned' => $totalPointsEarned,
            'points_total' => $totalPointsPossible,
        ]);

        $this->webhook->dispatch($session, 'test.completed');

        return response()->json(['score' => $finalScore, 'redirect' => route('test.result', $token)]);
    }

    public function result(string $token)
    {
        $session = TestSession::where('token', $token)
            ->where('status', 'completed')
            ->with('candidate')
            ->firstOrFail();

        return Inertia::render('Test/Completed', [
            'score' => $session->score,
            'candidate' => $session->candidate->full_name,
            'duration_seconds' => $session->duration_seconds,
            'total_questions' => $session->total_questions,
            'correct_answers' => $session->correct_answers,
            'points_earned' => $session->points_earned,
            'points_total' => $session->points_total,
        ]);
    }

    public function logActivity(Request $request, string $token)
    {
        $session = TestSession::where('token', $token)->where('status', 'in_progress')->firstOrFail();

        $request->validate(['event' => 'required|string', 'metadata' => 'nullable|array']);

        ActivityLog::create([
            'test_session_id' => $session->id,
            'event' => $request->event,
            'metadata' => $request->metadata,
            'occurred_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function maxPoints(\App\Models\Question $question): int
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

    private function scoreMcq($question, $answer): float
    {
        if (!$answer) {
            return 0;
        }

        $correctIds = $question->choices->where('is_correct', true)->pluck('id')->sort()->values();
        $givenIds = collect(is_array($answer) ? $answer : [$answer])->sort()->values();

        return $correctIds->toArray() === $givenIds->toArray() ? 100.0 : 0.0;
    }
}
