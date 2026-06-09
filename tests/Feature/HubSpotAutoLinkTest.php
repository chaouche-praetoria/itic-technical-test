<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\Candidate;
use App\Models\Domain;
use App\Models\TestSession;
use App\Models\TestTemplate;
use App\Services\HubSpotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubSpotAutoLinkTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;
    private AcademicLevel $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = Domain::create(['name' => 'Tech', 'slug' => 'tech']);
        $this->level = AcademicLevel::create(['name' => 'Bac+2', 'slug' => 'bac-2']);
    }

    /**
     * Test findByYpareoCode matches templates properly.
     */
    public function test_template_can_be_found_by_ypareo_code(): void
    {
        $template = TestTemplate::create([
            'name' => 'PHP/Laravel Test',
            'domain_id' => $this->domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
            'ypareo_codes' => 'DEV-WEB, DEV-LARAVEL, PHP-BASIC',
        ]);

        // Exact match
        $found = TestTemplate::findByYpareoCode('DEV-WEB');
        $this->assertNotNull($found);
        $this->assertEquals($template->id, $found->id);

        // Case insensitivity
        $foundCase = TestTemplate::findByYpareoCode('dev-laravel');
        $this->assertNotNull($foundCase);
        $this->assertEquals($template->id, $foundCase->id);

        // Spaces trimmed
        $foundSpaces = TestTemplate::findByYpareoCode(' PHP-BASIC ');
        $this->assertNotNull($foundSpaces);
        $this->assertEquals($template->id, $foundSpaces->id);

        // Non-matching code
        $foundNone = TestTemplate::findByYpareoCode('DEV-OPS');
        $this->assertNull($foundNone);
    }

    /**
     * Test contact sync automatically generates test link and pushes to HubSpot.
     */
    public function test_contact_sync_auto_generates_session_and_syncs_link_back(): void
    {
        $template = TestTemplate::create([
            'name' => 'PHP/Laravel Test',
            'domain_id' => $this->domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
            'ypareo_codes' => 'DEV-WEB',
        ]);
        $template->academicLevels()->attach($this->level->id);

        // Mock HubSpot Service to expect the updateContact call
        $mockHubspot = $this->getMockBuilder(HubSpotService::class)
            ->onlyMethods(['updateContact'])
            ->getMock();

        $mockHubspot->expects($this->once())
            ->method('updateContact')
            ->with('candidat.auto@example.com', $this->callback(function ($properties) {
                return $properties['resultat_test_technique'] === ''
                    && $properties['score_test_technique'] === ''
                    && $properties['date_test_technique'] === ''
                    && $properties['orientation_proposee'] === ''
                    && str_contains($properties['lien_test_technique'], '/test/');
            }))
            ->willReturn(true);

        $this->instance(HubSpotService::class, $mockHubspot);

        $contactData = [
            'id' => '998877',
            'properties' => [
                'email' => 'candidat.auto@example.com',
                'firstname' => 'Automated',
                'lastname' => 'User',
                'formation_souhaitee_pour_ypareo' => 'DEV-WEB',
            ]
        ];

        // Call syncSingleContact directly
        $candidate = $mockHubspot->syncSingleContact($contactData);

        $this->assertNotNull($candidate);
        $this->assertEquals('hubspot', $candidate->added_by);
        $this->assertEquals('998877', $candidate->hubspot_id);

        // Verify that a session was generated
        $session = TestSession::where('candidate_id', $candidate->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals($template->id, $session->test_template_id);
        $this->assertEquals('pending', $session->status);
    }

    /**
     * Test contact sync does not generate a duplicate session if one already exists.
     */
    public function test_contact_sync_does_not_duplicate_existing_session(): void
    {
        $template = TestTemplate::create([
            'name' => 'PHP/Laravel Test',
            'domain_id' => $this->domain->id,
            'duration_minutes' => 60,
            'is_active' => true,
            'ypareo_codes' => 'DEV-WEB',
        ]);

        $candidate = Candidate::create([
            'email' => 'existing.candidat@example.com',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'added_by' => 'hubspot',
        ]);

        // Create an existing test session
        $existingSession = TestSession::create([
            'candidate_id' => $candidate->id,
            'test_template_id' => $template->id,
            'token' => 'existing-token-123',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Mock HubSpot Service to expect NO updateContact calls
        $mockHubspot = $this->getMockBuilder(HubSpotService::class)
            ->onlyMethods(['updateContact'])
            ->getMock();

        $mockHubspot->expects($this->never())
            ->method('updateContact');

        $this->instance(HubSpotService::class, $mockHubspot);

        $contactData = [
            'id' => '998878',
            'properties' => [
                'email' => 'existing.candidat@example.com',
                'firstname' => 'Existing',
                'lastname' => 'User',
                'formation_souhaitee_pour_ypareo' => 'DEV-WEB',
            ]
        ];

        $mockHubspot->syncSingleContact($contactData);

        // Verify that we still have exactly 1 session
        $this->assertEquals(1, TestSession::where('candidate_id', $candidate->id)->count());
    }
}
