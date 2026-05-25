<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Domain;
use App\Models\Question;
use App\Models\TestSession;
use App\Models\TestSessionQuestion;
use App\Models\TestTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CandidateSubmitTest extends TestCase
{
    use RefreshDatabase;

    private $domain;
    private $template;
    private $academicLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicLevel = \App\Models\AcademicLevel::create(['name' => 'Bac+2', 'slug' => 'bac-2']);
        $this->domain = Domain::create(['name' => 'Programming', 'slug' => 'programming']);
        $this->template = TestTemplate::create([
            'name' => 'Laravel PHP Test',
            'domain_id' => $this->domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $this->template->refresh();
    }

    /**
     * Test submitting a test with only MCQ questions for a HubSpot candidate completes and syncs to HubSpot and updates candidate.
     */
    public function test_submit_mcq_only_test_for_hubspot_candidate_completes_and_syncs_to_hubspot_and_updates_local_candidate(): void
    {
        $candidate = Candidate::create([
            'first_name' => 'Alex',
            'last_name' => 'Tester',
            'email' => 'alex.tester@example.com',
            'added_by' => 'hubspot',
        ]);

        // 1. Create an MCQ question
        $question = Question::create([
            'theme_id' => null,
            'academic_level_id' => $this->academicLevel->id,
            'type' => 'mcq',
            'statement' => 'What is Laravel?',
            'difficulty' => 'easy',
            'points' => 10,
        ]);

        $choice = $question->choices()->create([
            'text' => 'A PHP Framework',
            'is_correct' => true,
        ]);

        // 2. Setup Test Session
        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'test_template_id' => $this->template->id,
            'token' => Str::random(40),
            'status' => 'in_progress',
            'expires_at' => now()->addHours(2),
            'started_at' => now()->subMinutes(15),
        ]);

        // Link question to session
        TestSessionQuestion::create([
            'test_session_id' => $session->id,
            'question_id' => $question->id,
            'order' => 0,
        ]);

        // Add candidate answer
        $session->answers()->create([
            'question_id' => $question->id,
            'answer' => [$choice->id],
        ]);

        // Mock HubSpot Service
        $mockHubspot = $this->createMock(\App\Services\HubSpotService::class);
        $mockHubspot->expects($this->once())
            ->method('updateContact')
            ->with('alex.tester@example.com', $this->callback(function ($properties) {
                return $properties['score_test_technique'] === '100.00'
                    && $properties['resultat_test_technique'] === 'admis'
                    && isset($properties['date_test_technique'])
                    && isset($properties['orientation_proposee']);
            }))
            ->willReturn(true);

        $this->instance(\App\Services\HubSpotService::class, $mockHubspot);

        // Submit the test
        $response = $this->post(route('test.submit', $session->token));

        $response->assertOk();
        $response->assertJson([
            'status' => 'completed',
            'score' => 100,
        ]);

        // Assert session status in DB is completed
        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertEquals(100.00, $session->score);

        // Assert Candidate local record was updated
        $candidate->refresh();
        $this->assertEquals('100.00', $candidate->score_test_technique);
        $this->assertEquals('admis', $candidate->resultat_test_technique);
        $this->assertEquals(now()->format('Y-m-d'), $candidate->date_test_technique);
    }

    /**
     * Test submitting a test with text questions for a HubSpot candidate goes to pending_review and syncs 'En cours de correction' to HubSpot and updates candidate locally.
     */
    public function test_submit_test_with_text_question_for_hubspot_candidate_awaits_review_and_syncs_in_progress_status_to_hubspot_and_updates_local_candidate(): void
    {
        $candidate = Candidate::create([
            'first_name' => 'Alex',
            'last_name' => 'Tester',
            'email' => 'alex.tester@example.com',
            'added_by' => 'hubspot',
        ]);

        // 1. Create a text question
        $question = Question::create([
            'theme_id' => null,
            'academic_level_id' => $this->academicLevel->id,
            'type' => 'text',
            'statement' => 'Describe dependency injection in your own words.',
            'difficulty' => 'medium',
            'points' => 20,
        ]);

        // 2. Setup Test Session
        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'test_template_id' => $this->template->id,
            'token' => Str::random(40),
            'status' => 'in_progress',
            'expires_at' => now()->addHours(2),
            'started_at' => now()->subMinutes(15),
        ]);

        // Link question to session
        TestSessionQuestion::create([
            'test_session_id' => $session->id,
            'question_id' => $question->id,
            'order' => 0,
        ]);

        // Add candidate answer
        $session->answers()->create([
            'question_id' => $question->id,
            'answer' => 'Dependency injection is...',
        ]);

        // Mock HubSpot Service
        $mockHubspot = $this->createMock(\App\Services\HubSpotService::class);
        $mockHubspot->expects($this->once())
            ->method('updateContact')
            ->with('alex.tester@example.com', $this->callback(function ($properties) {
                return $properties['score_test_technique'] === '0.00'
                    && $properties['resultat_test_technique'] === 'En cours de correction'
                    && isset($properties['date_test_technique'])
                    && isset($properties['orientation_proposee']);
            }))
            ->willReturn(true);

        $this->instance(\App\Services\HubSpotService::class, $mockHubspot);

        // Submit the test
        $response = $this->post(route('test.submit', $session->token));

        $response->assertOk();
        $response->assertJson([
            'status' => 'pending_review',
            'score' => 0,
        ]);

        // Assert session status in DB is pending_review
        $session->refresh();
        $this->assertEquals('pending_review', $session->status);
        $this->assertEquals(0.00, $session->score);

        // Assert Candidate local record was updated with 'En cours de correction'
        $candidate->refresh();
        $this->assertEquals('0.00', $candidate->score_test_technique);
        $this->assertEquals('En cours de correction', $candidate->resultat_test_technique);
        $this->assertEquals(now()->format('Y-m-d'), $candidate->date_test_technique);
    }

    /**
     * Test submitting a test for a non-HubSpot candidate does NOT call HubSpot Service, but updates local Candidate.
     */
    public function test_submit_test_for_non_hubspot_candidate_does_not_call_hubspot_but_updates_local_candidate(): void
    {
        $candidate = Candidate::create([
            'first_name' => 'Alex',
            'last_name' => 'Tester',
            'email' => 'alex.tester@example.com',
            'added_by' => 'manual', // or 'excel'
        ]);

        // 1. Create an MCQ question
        $question = Question::create([
            'theme_id' => null,
            'academic_level_id' => $this->academicLevel->id,
            'type' => 'mcq',
            'statement' => 'What is Laravel?',
            'difficulty' => 'easy',
            'points' => 10,
        ]);

        $choice = $question->choices()->create([
            'text' => 'A PHP Framework',
            'is_correct' => true,
        ]);

        // 2. Setup Test Session
        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'test_template_id' => $this->template->id,
            'token' => Str::random(40),
            'status' => 'in_progress',
            'expires_at' => now()->addHours(2),
            'started_at' => now()->subMinutes(15),
        ]);

        // Link question to session
        TestSessionQuestion::create([
            'test_session_id' => $session->id,
            'question_id' => $question->id,
            'order' => 0,
        ]);

        // Add candidate answer
        $session->answers()->create([
            'question_id' => $question->id,
            'answer' => [$choice->id],
        ]);

        // Mock HubSpot Service and assert it is NEVER called
        $mockHubspot = $this->createMock(\App\Services\HubSpotService::class);
        $mockHubspot->expects($this->never())
            ->method('updateContact');

        $this->instance(\App\Services\HubSpotService::class, $mockHubspot);

        // Submit the test
        $response = $this->post(route('test.submit', $session->token));

        $response->assertOk();

        // Assert Candidate local record was updated
        $candidate->refresh();
        $this->assertEquals('100.00', $candidate->score_test_technique);
        $this->assertEquals('admis', $candidate->resultat_test_technique);
        $this->assertEquals(now()->format('Y-m-d'), $candidate->date_test_technique);
    }
}
