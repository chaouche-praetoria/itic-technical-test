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
        private \App\Services\Judge0Service $judge0,
        private \App\Services\WebhookService $webhook,
        private \App\Services\TestScoringService $scoring,
        private \App\Services\HubSpotService $hubspot
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
            return Inertia::render('Test/Welcome', [
                'session' => [
                    'id' => $session->id,
                    'token' => $session->token,
                    'template_name' => $session->template->name,
                    'domain_name' => $session->template->domain->name,
                    'duration_minutes' => $session->template->duration_minutes,
                    'question_timer' => $session->template->question_timer,
                    'question_time_seconds' => $session->template->question_time_seconds,
                    'total_questions' => $session->sessionQuestions->count(),
                ],
                'candidate' => ['name' => $session->candidate->full_name],
            ]);
        }

        if ($session->sessionQuestions->count() === 0) {
            return Inertia::render('Test/NoQuestions', [
                'session' => [
                    'id' => $session->id,
                    'token' => $session->token,
                ],
                'candidate' => ['name' => $session->candidate->full_name],
            ]);
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

    public function begin(string $token)
    {
        $session = TestSession::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'ip_address' => request()->ip()
        ]);

        return redirect()->route('test.start', $token);
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
            ->with(['sessionQuestions.question', 'answers'])
            ->firstOrFail();

        // 1. Initial MCQ Scoring Update
        foreach ($session->sessionQuestions as $sq) {
            $question = $sq->question;
            if ($question->type === 'mcq') {
                $answerRecord = $session->answers->firstWhere('question_id', $question->id);
                if ($answerRecord) {
                    $score = $this->scoring->scoreMcq($question, $answerRecord->answer);
                    $answerRecord->update(['score' => $score]);
                }
            }
        }

        // 2. Determine Initial Status (Pending Review if text questions exist)
        $hasTextQuestions = $session->sessionQuestions->some(fn($sq) => $sq->question->type === 'text');
        $status = $hasTextQuestions ? 'pending_review' : 'completed';

        // 3. Centralized Recalculation
        $this->scoring->calculateSessionScores($session);

        $session->update([
            'status' => $status,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($session->started_at),
        ]);

        if ($status === 'completed' && $session->candidate && $session->candidate->email) {
            $scoreStr = number_format($session->score, 2);
            $resultLabel = $session->score >= 70 ? 'admis' : 'Echec - A requalifier';
            
            $this->hubspot->updateContact($session->candidate->email, [
                'score_test_technique' => $scoreStr,
                'resultat_test_technique' => $resultLabel,
                'date_test_technique' => now()->format('Y-m-d'),
            ]);
        }

        $this->webhook->dispatch($session, 'test.completed');

        return response()->json([
            'score' => $session->score,
            'status' => $status,
            'redirect' => route('test.result', $token)
        ]);
    }

    public function result(string $token)
    {
        $session = TestSession::where('token', $token)
            ->whereIn('status', ['completed', 'pending_review'])
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
        return $this->scoring->maxPoints($question);
    }

    private function scoreMcq($question, $answer): float
    {
        return $this->scoring->scoreMcq($question, $answer);
    }
}
