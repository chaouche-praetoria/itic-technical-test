<?php

namespace App\Jobs;

use App\Services\HubSpotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCandidateToHubSpot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param array $properties
     */
    public function __construct(
        protected string $email,
        protected array $properties
    ) {}

    /**
     * Execute the job.
     *
     * @param HubSpotService $hubspot
     * @return void
     */
    public function handle(HubSpotService $hubspot): void
    {
        Log::info("Running queued HubSpot sync for candidate email: {$this->email}", [
            'properties' => $this->properties,
        ]);

        $success = $hubspot->updateContact($this->email, $this->properties);

        if (!$success) {
            Log::error("Failed to sync contact to HubSpot in queued job for: {$this->email}");
        }
    }
}
