<?php

namespace Tests\Feature\Console;

use App\Models\Candidate;
use App\Services\HubSpotService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncHubSpotCandidatesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the console command executes successfully and imports/updates candidates.
     */
    public function test_console_command_syncs_candidates_successfully(): void
    {
        $mockHubspot = $this->createMock(HubSpotService::class);
        $mockHubspot->expects($this->once())
            ->method('syncContactsIntoDatabase')
            ->willReturn([
                'created' => 3,
                'updated' => 2,
            ]);

        $this->instance(HubSpotService::class, $mockHubspot);

        // Run the command and assert exit code and output
        $this->artisan('hubspot:sync-candidates')
            ->expectsOutput('Starting HubSpot candidates synchronization...')
            ->expectsOutput('HubSpot synchronization completed successfully. Created: 3, Updated: 2.')
            ->assertExitCode(0);
    }

    /**
     * Test the console command handles exception cleanly.
     */
    public function test_console_command_handles_exceptions(): void
    {
        $mockHubspot = $this->createMock(HubSpotService::class);
        $mockHubspot->expects($this->once())
            ->method('syncContactsIntoDatabase')
            ->willThrowException(new \Exception('API connection timeout'));

        $this->instance(HubSpotService::class, $mockHubspot);

        // Run the command and assert failure
        $this->artisan('hubspot:sync-candidates')
            ->expectsOutput('Starting HubSpot candidates synchronization...')
            ->expectsOutput('HubSpot synchronization failed: API connection timeout')
            ->assertExitCode(1);
    }

    /**
     * Test the command is scheduled daily at 11:00.
     */
    public function test_command_is_scheduled_at_eleven_am(): void
    {
        $schedule = app(Schedule::class);

        $events = collect($schedule->events())->filter(function (Event $event) {
            return str_contains($event->command, 'hubspot:sync-candidates');
        });

        $this->assertCount(1, $events, 'The hubspot:sync-candidates command is not scheduled.');

        $event = $events->first();

        // 11:00 AM translates to cron expression '0 11 * * *'
        $this->assertEquals('0 11 * * *', $event->expression);
    }
}
