<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\TestSession;
use App\Models\TestTemplate;
use App\Services\TestGeneratorService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function __construct(private TestGeneratorService $generator) {}

    public function createCandidate(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $candidate = Candidate::create($validated);

        return response()->json($candidate, 201);
    }

    public function generateLink(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'test_template_id' => 'required|exists:test_templates,id',
        ]);

        $candidate = Candidate::findOrFail($request->candidate_id);
        $template = TestTemplate::findOrFail($request->test_template_id);

        $session = $this->generator->generateSession($candidate, $template);

        return response()->json([
            'session_id' => $session->id,
            'token' => $session->token,
            'link' => route('test.start', $session->token),
            'expires_at' => $session->expires_at->toIso8601String(),
        ]);
    }

    public function getResults(Request $request, $sessionId)
    {
        $session = TestSession::with(['candidate', 'template', 'answers.question'])
            ->findOrFail($sessionId);

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'candidate' => [
                'id' => $session->candidate->id,
                'name' => $session->candidate->full_name,
                'email' => $session->candidate->email,
            ],
            'template' => $session->template->name,
            'score' => $session->score,
            'total_questions' => $session->total_questions,
            'correct_answers' => $session->correct_answers,
            'duration_seconds' => $session->duration_seconds,
            'started_at' => $session->started_at?->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'answers' => $session->answers->map(fn($a) => [
                'question_id' => $a->question_id,
                'type' => $a->question->type,
                'score' => $a->score,
                'time_spent_seconds' => $a->time_spent_seconds,
            ]),
        ]);
    }
}
