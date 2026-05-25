<?php

namespace Tests\Feature\Admin;

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Imports\CandidatesImport;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup RBAC
        $manageCandidates = Permission::create(['name' => 'manage-candidates', 'label' => 'Gérer les candidats']);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $adminRole->permissions()->attach($manageCandidates);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Dynamically define the gate for testing
        \Illuminate\Support\Facades\Gate::define('manage-candidates', function ($user) {
            return $user->hasPermission('manage-candidates');
        });
    }

    /**
     * Test creating a candidate manually.
     */
    public function test_admin_can_create_candidate_manually(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.candidates.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '0601020304',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('candidates', [
            'email' => 'john.doe@example.com',
            'added_by' => 'manual',
        ]);
    }

    /**
     * Test Excel importer assigns the 'excel' source only on creation.
     */
    public function test_excel_import_sets_source_to_excel_on_creation_only(): void
    {
        $importer = new CandidatesImport();

        // 1. Create a candidate via import
        $row1 = [
            'email' => 'excel.new@example.com',
            'prenom' => 'Bob',
            'nom' => 'Smith',
            'telephone' => '0707070707',
            'formation' => 'Dev Web',
        ];

        $candidate1 = $importer->model($row1);
        $candidate1->save();

        $this->assertDatabaseHas('candidates', [
            'email' => 'excel.new@example.com',
            'added_by' => 'excel',
        ]);

        // 2. Update same candidate via import (should not alter origin)
        $row2 = [
            'email' => 'excel.new@example.com',
            'prenom' => 'Bob Updated',
            'nom' => 'Smith',
            'telephone' => '0707070707',
            'formation' => 'Dev Web Plus',
        ];

        $candidate2 = $importer->model($row2);
        $candidate2->save();

        $this->assertDatabaseHas('candidates', [
            'email' => 'excel.new@example.com',
            'first_name' => 'Bob Updated',
            'added_by' => 'excel', // preserved
        ]);
    }

    /**
     * Test HubSpot sync updates added_by if different.
     */
    public function test_hubspot_sync_updates_added_by_if_different(): void
    {
        $candidate = Candidate::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '0600000000',
            'added_by' => 'excel',
        ]);

        $mockHubspot = $this->createMock(\App\Services\HubSpotService::class);
        $mockHubspot->expects($this->once())
            ->method('getContact')
            ->with('jane.doe@example.com')
            ->willReturn([
                'id' => '12345',
                'properties' => [
                    'firstname' => 'Jane',
                    'lastname' => 'Doe',
                    'phone' => '0600000000',
                    'formation_souhaitee' => 'Dev Web',
                ]
            ]);

        $this->instance(\App\Services\HubSpotService::class, $mockHubspot);

        $response = $this->actingAs($this->admin)->post(route('admin.candidates.pull-data', $candidate->id));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
            'email' => 'jane.doe@example.com',
            'added_by' => 'hubspot',
        ]);
    }

    public function test_bulk_destroy_deletes_candidates(): void
    {
        $candidate1 = Candidate::create([
            'first_name' => 'First1',
            'last_name' => 'Last1',
            'email' => 'first1@example.com',
            'added_by' => 'manual',
        ]);
        $candidate2 = Candidate::create([
            'first_name' => 'First2',
            'last_name' => 'Last2',
            'email' => 'first2@example.com',
            'added_by' => 'manual',
        ]);
        $candidate3 = Candidate::create([
            'first_name' => 'First3',
            'last_name' => 'Last3',
            'email' => 'first3@example.com',
            'added_by' => 'manual',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.candidates.bulk-destroy'), [
            'ids' => [$candidate1->id, $candidate2->id]
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('candidates', ['id' => $candidate1->id]);
        $this->assertDatabaseMissing('candidates', ['id' => $candidate2->id]);
        $this->assertDatabaseHas('candidates', ['id' => $candidate3->id]);
    }

    public function test_bulk_generate_link_generates_and_dispatches(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $domain = \App\Models\Domain::create(['name' => 'Test Domain', 'slug' => 'test-domain']);
        $template = \App\Models\TestTemplate::create([
            'name' => 'Template A',
            'domain_id' => $domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $template->refresh();

        $candidate1 = Candidate::create([
            'first_name' => 'C1',
            'last_name' => 'L1',
            'email' => 'c1@example.com',
            'added_by' => 'manual',
        ]);
        $candidate2 = Candidate::create([
            'first_name' => 'C2',
            'last_name' => 'L2',
            'email' => 'c2@example.com',
            'added_by' => 'manual',
        ]);

        $mockHubspot = $this->createMock(\App\Services\HubSpotService::class);
        $mockHubspot->expects($this->exactly(2))
            ->method('updateContact')
            ->willReturn(true);
        $this->instance(\App\Services\HubSpotService::class, $mockHubspot);

        $response = $this->actingAs($this->admin)->post(route('admin.candidates.bulk-generate-link'), [
            'ids' => [$candidate1->id, $candidate2->id],
            'test_template_id' => $template->id,
            'send_email' => true,
            'sync_hubspot' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Liens générés pour 2 candidats avec succès.');
        
        // Assert session created
        $this->assertCount(2, \App\Models\TestSession::all());

        // Assert mail was queued (since it is a ShouldQueue mailable)
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\TestInvitationMail::class, 2);
    }

    public function test_can_filter_candidates_by_test_session_presence(): void
    {
        $domain = \App\Models\Domain::create(['name' => 'Tech', 'slug' => 'tech']);
        $template = \App\Models\TestTemplate::create([
            'name' => 'Backend Test',
            'domain_id' => $domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $template->refresh();

        $candidate1 = Candidate::create([
            'first_name' => 'Has',
            'last_name' => 'Session',
            'email' => 'has.session@example.com',
            'added_by' => 'manual',
        ]);

        $candidate2 = Candidate::create([
            'first_name' => 'No',
            'last_name' => 'Session',
            'email' => 'no.session@example.com',
            'added_by' => 'manual',
        ]);

        // Generate a session for candidate 1
        \App\Models\TestSession::create([
            'candidate_id' => $candidate1->id,
            'test_template_id' => $template->id,
            'token' => \Illuminate\Support\Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // 1. Filter with has_sessions = yes
        $response = $this->actingAs($this->admin)->get(route('admin.candidates.index', [
            'has_sessions' => 'yes'
        ]));

        $response->assertOk();
        $inertiaData = $response->original->getData()['page']['props'];
        $candidates = $inertiaData['candidates']['data'];
        
        $candidateIds = collect($candidates)->pluck('id')->all();
        $this->assertContains($candidate1->id, $candidateIds);
        $this->assertNotContains($candidate2->id, $candidateIds);

        // 2. Filter with has_sessions = no
        $response = $this->actingAs($this->admin)->get(route('admin.candidates.index', [
            'has_sessions' => 'no'
        ]));

        $response->assertOk();
        $inertiaData = $response->original->getData()['page']['props'];
        $candidates = $inertiaData['candidates']['data'];
        
        $candidateIds = collect($candidates)->pluck('id')->all();
        $this->assertContains($candidate2->id, $candidateIds);
        $this->assertNotContains($candidate1->id, $candidateIds);

        // 3. Assert correct stats are returned
        $stats = $inertiaData['stats'];
        $this->assertEquals(1, $stats['candidates_with_sessions']);
        $this->assertEquals(1, $stats['candidates_without_sessions']);
    }

    public function test_can_filter_candidates_by_test_completed_status(): void
    {
        $domain = \App\Models\Domain::create(['name' => 'Tech', 'slug' => 'tech-filter']);
        $template = \App\Models\TestTemplate::create([
            'name' => 'Backend Test',
            'domain_id' => $domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $template->refresh();

        $candidate1 = Candidate::create([
            'first_name' => 'Done',
            'last_name' => 'Test',
            'email' => 'done.test@example.com',
            'added_by' => 'manual',
        ]);

        $candidate2 = Candidate::create([
            'first_name' => 'NotDone',
            'last_name' => 'Test',
            'email' => 'notdone.test@example.com',
            'added_by' => 'manual',
        ]);

        // Completed session for candidate 1
        \App\Models\TestSession::create([
            'candidate_id' => $candidate1->id,
            'test_template_id' => $template->id,
            'token' => \Illuminate\Support\Str::random(40),
            'status' => 'completed',
            'score' => 85,
            'expires_at' => now()->addDays(7),
        ]);

        // Pending session for candidate 2 (not yet completed)
        \App\Models\TestSession::create([
            'candidate_id' => $candidate2->id,
            'test_template_id' => $template->id,
            'token' => \Illuminate\Support\Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // 1. Filter with test_completed = yes
        $response = $this->actingAs($this->admin)->get(route('admin.candidates.index', [
            'test_completed' => 'yes'
        ]));

        $response->assertOk();
        $inertiaData = $response->original->getData()['page']['props'];
        $candidates = $inertiaData['candidates']['data'];
        
        $candidateIds = collect($candidates)->pluck('id')->all();
        $this->assertContains($candidate1->id, $candidateIds);
        $this->assertNotContains($candidate2->id, $candidateIds);

        // 2. Filter with test_completed = no
        $response = $this->actingAs($this->admin)->get(route('admin.candidates.index', [
            'test_completed' => 'no'
        ]));

        $response->assertOk();
        $inertiaData = $response->original->getData()['page']['props'];
        $candidates = $inertiaData['candidates']['data'];
        
        $candidateIds = collect($candidates)->pluck('id')->all();
        $this->assertContains($candidate2->id, $candidateIds);
        $this->assertNotContains($candidate1->id, $candidateIds);
    }

    public function test_bulk_sync_to_hubspot_updates_candidates_on_hubspot(): void
    {
        $domain = \App\Models\Domain::create(['name' => 'Tech', 'slug' => 'tech-bulk']);
        $template = \App\Models\TestTemplate::create([
            'name' => 'Backend Test',
            'domain_id' => $domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $template->refresh();

        $candidate1 = Candidate::create([
            'first_name' => 'Hub1',
            'last_name' => 'Smith',
            'email' => 'hub1@example.com',
            'added_by' => 'hubspot',
        ]);

        $candidate2 = Candidate::create([
            'first_name' => 'Hub2',
            'last_name' => 'Jones',
            'email' => 'hub2@example.com',
            'added_by' => 'hubspot',
        ]);

        // Non-HubSpot candidate (should be ignored by bulk sync)
        $candidate3 = Candidate::create([
            'first_name' => 'Manual',
            'last_name' => 'Doe',
            'email' => 'manual@example.com',
            'added_by' => 'manual',
        ]);

        // Completed graded session for candidate 1
        $session1 = \App\Models\TestSession::create([
            'candidate_id' => $candidate1->id,
            'test_template_id' => $template->id,
            'token' => \Illuminate\Support\Str::random(40),
            'status' => 'completed',
            'score' => 85.5,
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        // Pending session (ungraded/uncompleted) for candidate 2
        $session2 = \App\Models\TestSession::create([
            'candidate_id' => $candidate2->id,
            'test_template_id' => $template->id,
            'token' => \Illuminate\Support\Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $mockHubspot = $this->createMock(\App\Services\HubSpotService::class);
        
        // Expect exact property payloads to be synced
        $mockHubspot->expects($this->exactly(2))
            ->method('updateContact')
            ->willReturnCallback(function ($email, $properties) use ($candidate1, $candidate2, $session1, $session2) {
                if ($email === $candidate1->email) {
                    $this->assertEquals('85.50', $properties['score_test_technique']);
                    $this->assertEquals('admis', $properties['resultat_test_technique']);
                    $this->assertEquals(route('test.start', $session1->token), $properties['lien_test_technique']);
                    return true;
                }
                if ($email === $candidate2->email) {
                    $this->assertEquals('', $properties['score_test_technique']);
                    $this->assertEquals('', $properties['resultat_test_technique']);
                    $this->assertEquals(route('test.start', $session2->token), $properties['lien_test_technique']);
                    return true;
                }
                $this->fail("Unexpected email synced: " . $email);
            });

        $this->instance(\App\Services\HubSpotService::class, $mockHubspot);

        $response = $this->actingAs($this->admin)->post(route('admin.candidates.bulk-sync-hubspot'), [
            'ids' => [$candidate1->id, $candidate2->id, $candidate3->id]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check local database updates for candidate 1
        $this->assertDatabaseHas('candidates', [
            'id' => $candidate1->id,
            'score_test_technique' => '85.50',
            'resultat_test_technique' => 'admis',
        ]);
    }
}
