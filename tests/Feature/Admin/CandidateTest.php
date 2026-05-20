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

        $response = $this->actingAs($this->admin)->post(route('admin.candidates.sync-specific', $candidate->id));

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

        $domain = \App\Models\Domain::create(['name' => 'Test Domain']);
        $template = \App\Models\TestTemplate::create([
            'name' => 'Template A',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

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

        $response->assertRedirect();
        
        // Assert session created
        $this->assertCount(2, \App\Models\TestSession::all());

        // Assert mail was sent
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TestInvitationMail::class, 2);
    }

    public function test_can_filter_candidates_by_test_session_presence(): void
    {
        $domain = \App\Models\Domain::create(['name' => 'Tech']);
        $template = \App\Models\TestTemplate::create([
            'name' => 'Backend Test',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

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
}
