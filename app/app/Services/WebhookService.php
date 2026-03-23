<?php

namespace App\Services;

use App\Models\TestSession;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private ?string $url;

    public function __construct()
    {
        $this->url = config('services.webhook.url');
    }

    public function dispatch(TestSession $session, string $event): void
    {
        if (!$this->url) {
            return;
        }

        $payload = $this->buildPayload($session, $event);
        $attempt = 1;
        $success = false;
        $responseStatus = null;
        $responseBody = null;

        try {
            $response = Http::timeout(10)->post($this->url, $payload);
            $responseStatus = $response->status();
            $responseBody = $response->body();
            $success = $response->successful();
        } catch (\Exception $e) {
            $responseBody = $e->getMessage();
            Log::error("Webhook failed: {$e->getMessage()}");
        }

        WebhookLog::create([
            'test_session_id' => $session->id,
            'url' => $this->url,
            'event' => $event,
            'payload' => $payload,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'success' => $success,
            'attempt' => $attempt,
        ]);
    }

    private function buildPayload(TestSession $session, string $event): array
    {
        $session->loadMissing(['candidate', 'template']);

        return [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'session' => [
                'id' => $session->id,
                'token' => $session->token,
                'status' => $session->status,
                'score' => $session->score,
                'link' => route('test.start', $session->token),
            ],
            'candidate' => [
                'id' => $session->candidate->id,
                'name' => $session->candidate->full_name,
                'email' => $session->candidate->email,
            ],
            'template' => [
                'id' => $session->template->id,
                'name' => $session->template->name,
            ],
        ];
    }
}
