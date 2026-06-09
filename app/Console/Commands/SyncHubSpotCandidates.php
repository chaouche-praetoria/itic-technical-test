<?php

namespace App\Console\Commands;

use App\Services\HubSpotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncHubSpotCandidates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:sync-candidates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize candidates/students from HubSpot into the local database';

    /**
     * Execute the console command.
     *
     * @param HubSpotService $hubspotService
     * @return int
     */
    public function handle(HubSpotService $hubspotService): int
    {
        $this->info('Starting HubSpot candidates synchronization...');
        Log::info('Console: Starting HubSpot candidates synchronization...');

        try {
            $stats = $hubspotService->syncContactsIntoDatabase();

            $successMessage = sprintf(
                'HubSpot synchronization completed successfully. Created: %d, Updated: %d.',
                $stats['created'],
                $stats['updated']
            );

            $this->info($successMessage);
            Log::info('Console: ' . $successMessage);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $errorMessage = 'HubSpot synchronization failed: ' . $e->getMessage();
            $this->error($errorMessage);
            Log::error('Console: ' . $errorMessage);

            return Command::FAILURE;
        }
    }
}
