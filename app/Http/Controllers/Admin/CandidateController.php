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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function __construct(
        private TestGeneratorService $generator,
        private WebhookService $webhook,
        private \App\Services\TestScoringService $scoring,
        private \App\Services\HubSpotService $hubspot
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
            $this->hubspot->updateContact($candidate->email, [
                'resultat_test_technique' => '',
                'score_test_technique' => '',
                'date_test_technique' => '',
                'orientation_proposee' => '',
                'lien_test_technique' => route('test.start', $session->token),
            ]);
            $successMessage .= " et synchronisé avec HubSpot pour " . $candidate->first_name;
        }

        $this->webhook->dispatch($session, 'test.link_generated');

        return back()->with([
            'success' => $successMessage,
            'last_session_id' => $session->id,
            'generated_link' => route('test.start', $session->token)
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
        $session->load(['candidate']);
        
        $this->hubspot->updateContact($session->candidate->email, [
            'resultat_test_technique' => '',
            'score_test_technique' => '',
            'date_test_technique' => '',
            'orientation_proposee' => '',
            'lien_test_technique' => route('test.start', $session->token),
        ]);

        return back()->with('success', "Lien de test synchronisé avec HubSpot pour " . $session->candidate->first_name);
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

        // Sync to HubSpot
        $session->load(['candidate', 'template.academicLevel']);
        if ($session->candidate && $session->candidate->email) {
            $scoreStr = number_format($session->score, 2);
            $resultLabel = $session->score >= 70 ? 'admis' : 'Echec - A requalifier';
            
            $this->hubspot->updateContact($session->candidate->email, [
                'score_test_technique' => $scoreStr,
                'resultat_test_technique' => $resultLabel,
                'date_test_technique' => now()->format('Y-m-d'),
                'orientation_proposee' => $this->scoring->getProposedOrientation($session),
                'lien_test_technique' => route('test.start', $session->token),
            ]);
        }

        return back()->with('success', 'Session marquée comme complétée et synchronisée avec HubSpot.');
    }

    public function syncToHubSpot(Candidate $candidate)
    {
        Log::info("HubSpot: Starting PUSH to HubSpot for candidate: " . $candidate->id);
        
        // Get the latest completed session or the latest session with a score
        $session = $candidate->testSessions()
            ->whereNotNull('score')
            ->latest()
            ->first();

        if (!$session) {
            return back()->with('error', 'Aucune session avec un score trouvée pour ce candidat.');
        }

        if (!$candidate->email) {
            return back()->with('error', 'L\'email du candidat est manquant.');
        }

        $scoreStr = number_format($session->score, 2);
        $resultLabel = $session->score >= 70 ? 'admis' : 'Echec - A requalifier';
        
        Log::info("HubSpot: Starting manual sync for " . $candidate->email, [
            'score' => $scoreStr,
            'result' => $resultLabel
        ]);

        $success = $this->hubspot->updateContact($candidate->email, [
            'score_test_technique' => $scoreStr,
            'resultat_test_technique' => $resultLabel,
            'date_test_technique' => $session->completed_at ? $session->completed_at->format('Y-m-d') : now()->format('Y-m-d'),
            'orientation_proposee' => $this->scoring->getProposedOrientation($session),
            'lien_test_technique' => route('test.start', $session->token),
        ]);

        Log::info("HubSpot: Manual sync completed for " . $candidate->email . ". Result: " . ($success ? 'SUCCESS' : 'FAILURE'));

        if ($success) {
            return back()->with('success', 'Synchronisation HubSpot réussie pour ' . $candidate->full_name);
        }

        return back()->with('error', 'Erreur lors de la synchronisation avec HubSpot.');
    }

    public function syncSpecificFromHubSpot(Candidate $candidate)
    {
        Log::info("PULL: syncSpecificFromHubSpot called for candidate: " . $candidate->id);
        
        if (!$candidate->email) {
            return back()->with('error', 'L\'email du candidat est manquant.');
        }

        $data = $this->hubspot->getContact($candidate->email);

        if (!$data || !isset($data['properties'])) {
            Log::error("PULL: No contact found on HubSpot for " . $candidate->email);
            return back()->with('error', 'Impossible de récupérer les données depuis HubSpot pour ce candidat.');
        }

        $props = $data['properties'];

        $candidate->update([
            'hubspot_id' => $data['id'],
            'first_name' => $props['firstname'] ?? $candidate->first_name,
            'last_name' => $props['lastname'] ?? $candidate->last_name,
            'phone' => $props['phone'] ?? $candidate->phone,
            'formation_souhaitee' => $props['formation_souhaitee'] ?? $candidate->formation_souhaitee,
            'formation_souhaitee_pour_ypareo' => $props['formation_souhaitee_pour_ypareo'] ?? $candidate->formation_souhaitee_pour_ypareo,
            'score_test_technique' => $props['score_test_technique'] ?? $candidate->score_test_technique,
            'resultat_test_technique' => $props['resultat_test_technique'] ?? $candidate->resultat_test_technique,
            'date_test_technique' => $props['date_test_technique'] ?? $candidate->date_test_technique,
            'orientation_proposee' => $props['orientation_proposee'] ?? $candidate->orientation_proposee,
            'lien_test_technique' => $props['lien_test_technique'] ?? $candidate->lien_test_technique,
        ]);

        return back()->with('success', 'Données rafraîchies depuis HubSpot pour ' . $candidate->full_name);
    }

    public function syncFromHubSpot()
    {
        $data = $this->hubspot->searchCandidates();

        if (!$data || !isset($data['results'])) {
            return back()->with('error', 'Impossible de récupérer les candidats depuis HubSpot.');
        }

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($data['results'] as $result) {
            $props = $result['properties'];
            $hubspotId = $result['id'];
            $email = $props['email'] ?? null;

            if (!$email) continue;

            $candidate = Candidate::where('hubspot_id', $hubspotId)
                ->orWhere('email', $email)
                ->first();

            $candidateData = [
                'hubspot_id' => $hubspotId,
                'first_name' => $props['firstname'] ?? 'Inconnu',
                'last_name' => $props['lastname'] ?? 'Inconnu',
                'email' => $email,
                'phone' => $props['phone'] ?? null,
                'formation_souhaitee' => $props['formation_souhaitee'] ?? null,
                'formation_souhaitee_pour_ypareo' => $props['formation_souhaitee_pour_ypareo'] ?? null,
                'score_test_technique' => $props['score_test_technique'] ?? null,
                'resultat_test_technique' => $props['resultat_test_technique'] ?? null,
                'date_test_technique' => $props['date_test_technique'] ?? null,
                'orientation_proposee' => $props['orientation_proposee'] ?? null,
                'lien_test_technique' => $props['lien_test_technique'] ?? null,
            ];

            /** @var \App\Models\Candidate $candidate */
            if ($candidate) {
                $candidate->update($candidateData);
                $updatedCount++;
            } else {
                Candidate::create($candidateData);
                $createdCount++;
            }
        }

        return back()->with('success', "Sync terminée : {$createdCount} importés, {$updatedCount} mis à jour.");
    }
}
