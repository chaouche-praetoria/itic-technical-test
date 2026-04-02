<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\TestSession;
use App\Models\TestTemplate;
use App\Services\TestGeneratorService;
use App\Services\WebhookService;
use App\Mail\TestInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function __construct(
        private TestGeneratorService $generator,
        private WebhookService $webhook,
        private \App\Services\TestScoringService $scoring
    ) {}

    public function index(Request $request)
    {
        $candidates = Candidate::withCount('testSessions')
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $candidates,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Candidate $candidate)
    {
        $sessions = $candidate->testSessions()
            ->with('template.domain')
            ->latest()
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'template' => $s->template->name,
                'domain' => $s->template->domain->name,
                'status' => $s->status,
                'score' => $s->score,
                'started_at' => $s->started_at?->toDateTimeString(),
                'completed_at' => $s->completed_at?->toDateTimeString(),
                'duration_seconds' => $s->duration_seconds,
                'token' => $s->token,
            ]);

        return Inertia::render('Admin/Candidates/Show', [
            'candidate' => $candidate,
            'sessions' => $sessions,
            'templates' => TestTemplate::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $candidate = Candidate::create($validated);
        return redirect()->route('admin.candidates.show', $candidate)->with('success', 'Candidat créé.');
    }

    public function generateLink(Request $request, Candidate $candidate)
    {
        $request->validate([
            'test_template_id' => 'required|exists:test_templates,id',
            'send_email' => 'boolean'
        ]);

        $template = TestTemplate::findOrFail($request->test_template_id);

        $session = $this->generator->generateSession($candidate, $template);

        $successMessage = "Lien généré: " . route('test.start', $session->token);

        if ($request->send_email) {
            $session->load(['candidate', 'template.domain']);
            Mail::to($session->candidate->email)->send(new TestInvitationMail($session));
            $successMessage .= " et envoyé par email à " . $candidate->first_name;
        }

        $this->webhook->dispatch($session, 'test.link_generated');

        return back()->with([
            'success' => $successMessage,
            'last_session_id' => $session->id
        ]);
    }

    public function sessionDetail(TestSession $session)
    {
        $session->load([
            'candidate',
            'template',
            'sessionQuestions.question.choices',
            'answers',
            'activityLogs',
        ]);

        return Inertia::render('Admin/Candidates/SessionDetail', ['session' => $session]);
    }

    public function sendSessionEmail(TestSession $session)
    {
        $session->load(['candidate', 'template.domain']);
        
        Mail::to($session->candidate->email)->send(new TestInvitationMail($session));

        return back()->with('success', "Lien de test envoyé par mail à " . $session->candidate->first_name);
    }

    public function gradeAnswer(Request $request, TestSession $session)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'score' => 'required|numeric|min:0|max:100',
        ]);

        $answer = $session->answers()->where('question_id', $request->question_id)->first();
        
        if (!$answer) {
            // Create a dummy answer if not exists (should not happen if they submitted)
            $answer = $session->answers()->create([
                'question_id' => $request->question_id,
                'answer' => '',
            ]);
        }

        $answer->update(['score' => $request->score]);

        // Recalculate everything
        $this->scoring->calculateSessionScores($session);

        return back()->with('success', 'Note mise à jour.');
    }

    public function finalizeSession(TestSession $session)
    {
        $session->update(['status' => 'completed']);
        return back()->with('success', 'Session marquée comme complétée.');
    }
}
