<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubSpotService
{
    private ?string $accessToken;
    private string $baseUrl = 'https://api.hubapi.com/crm/v3/objects/contacts';

    public function __construct()
    {
        $this->accessToken = config('services.hubspot.api_key');
    }

    /**
     * Search for candidates in HubSpot.
     * 
     * @param string|array $statusValue The value(s) for statut_ypareo_candidat__ne_pas_modifier_ (default ["145", "162"])
     * @return array|null
     */
    public function searchCandidates(string|array $statusValue = ["145", "162"]): ?array
    {
        if (!$this->accessToken) {
            Log::error("HubSpot Access Token is missing in configuration.");
            return null;
        }

        $statusValues = is_array($statusValue) ? $statusValue : [$statusValue];

        $filterGroups = [];
        foreach ($statusValues as $status) {
            $filterGroups[] = [
                "filters" => [
                    [
                        "propertyName" => "statut_ypareo_candidat__ne_pas_modifier_",
                        "operator" => "EQ",
                        "value" => (string)$status
                    ]
                ]
            ];
        }

        $allResults = [];
        $after = null;

        do {
            $payload = [
                "filterGroups" => $filterGroups,
                "properties" => [
                    "email",
                    "firstname",
                    "lastname",
                    "phone",
                    "formation_souhaitee",
                    "formation_souhaitee_pour_ypareo",
                    "score_test_technique",
                    "resultat_test_technique",
                    "date_test_technique",
                    "orientation_proposee",
                    "lien_test_technique"
                ],
                "limit" => 200
            ];

            if ($after) {
                $payload["after"] = $after;
            }

            try {
                Log::info("HubSpot: Searching contacts, page limit 200" . ($after ? " (after: {$after})" : ""));
                $response = Http::withToken($this->accessToken)
                    ->post("{$this->baseUrl}/search", $payload);

                if (!$response->successful()) {
                    Log::error("HubSpot API Search Error: " . $response->body());
                    return empty($allResults) ? null : ['results' => $allResults];
                }

                $data = $response->json();
                if (isset($data['results']) && is_array($data['results'])) {
                    $allResults = array_merge($allResults, $data['results']);
                }

                $after = $data['paging']['next']['after'] ?? null;
            } catch (\Exception $e) {
                Log::error("HubSpot Service Exception during search: " . $e->getMessage());
                return empty($allResults) ? null : ['results' => $allResults];
            }
        } while ($after);

        Log::info("HubSpot Search Completed: " . count($allResults) . " candidates retrieved in total.");
        return ['results' => $allResults];
    }

    /**
     * Get a contact by email from HubSpot.
     * 
     * @param string $email
     * @return array|null
     */
    public function getContact(string $email): ?array
    {
        if (!$this->accessToken) {
            Log::error("HubSpot Access Token is missing in configuration.");
            return null;
        }

        $properties = [
            "email",
            "firstname",
            "lastname",
            "phone",
            "formation_souhaitee",
            "formation_souhaitee_pour_ypareo",
            "score_test_technique",
            "resultat_test_technique",
            "date_test_technique",
            "orientation_proposee",
            "lien_test_technique"
        ];

        try {
            Log::info("HubSpot: Fetching contact " . $email);
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/{$email}?idProperty=email&properties=" . implode(',', $properties));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("HubSpot API Get Error for {$email}: " . $response->status() . " - " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("HubSpot Get Service Exception for {$email}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the mapping of Ypareo codes to their human-readable labels
     * for the "formation_souhaitee_pour_ypareo" HubSpot property.
     *
     * @return array<string, string> Map of [ypareo_code => label]
     */
    private function getFormationLabels(): array
    {
        return Cache::remember(
            'hubspot_formation_labels',
            now()->addHours(24),
            function () {

                $response = Http::withToken($this->accessToken)
                    ->get(
                        'https://api.hubapi.com/crm/v3/properties/contacts/formation_souhaitee_pour_ypareo'
                    );

                if (!$response->successful()) {
                    Log::error(
                        'Impossible de récupérer les labels HubSpot formation_souhaitee_pour_ypareo'
                    );

                    return [];
                }

                return collect($response->json('options'))
                    ->pluck('label', 'value')
                    ->toArray();
            }
        );
    }

    /**
     * Update a contact's properties in HubSpot.
     * 
     * @param string $email
     * @param array $properties
     * @return bool
     */
    public function updateContact(string $email, array $properties): bool
    {
        if (!$this->accessToken) {
            Log::error("HubSpot Access Token is missing in configuration.");
            return false;
        }

        try {
            Log::info("HubSpot: Sending PATCH request to " . "{$this->baseUrl}/{$email}?idProperty=email", [
                'properties' => $properties
            ]);

            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->patch("{$this->baseUrl}/{$email}?idProperty=email", [
                    'properties' => $properties
                ]);

            Log::info("HubSpot: Received response status: " . $response->status());

            if ($response->successful()) {
                Log::info("HubSpot Contact Updated Successfully: {$email}");
                return true;
            }

            Log::error("HubSpot API Update Error for {$email}: " . $response->status() . " - " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("HubSpot Update Service Exception for {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch candidates from HubSpot and sync them into the local database.
     * 
     * @return array Array with keys 'created' and 'updated' representing counts.
     */
    public function syncContactsIntoDatabase(): array
    {
        $data = $this->searchCandidates();

        if (!$data || !isset($data['results'])) {
            Log::error("syncContactsIntoDatabase: No candidates found or HubSpot search failed.");
            throw new \Exception('No candidates found or HubSpot search failed.');
        }

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($data['results'] as $result) {
            $email = $result['properties']['email'] ?? null;
            if (!$email) continue;

            $candidateExists = Candidate::where('hubspot_id', $result['id'])
                ->orWhere('email', $email)
                ->exists();

            $this->syncSingleContact($result);

            if ($candidateExists) {
                $updatedCount++;
            } else {
                $createdCount++;
            }
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount
        ];
    }

    /**
     * Process a single HubSpot contact result and sync it to the local candidates table.
     * If a matching test template is found for their Ypareo code and they don't have
     * any test session yet, automatically generate a session and push the link back to HubSpot.
     * 
     * @param array $result HubSpot API contact structure (with 'id' and 'properties')
     * @param string|null $fallbackEmail Optional email to fallback to if not in HubSpot payload
     * @return Candidate
     */
    public function syncSingleContact(array $result, ?string $fallbackEmail = null): Candidate
    {
        $props = $result['properties'];
        $hubspotId = $result['id'];
        $email = $props['email'] ?? $fallbackEmail;

        if (!$email) {
            throw new \InvalidArgumentException("HubSpot contact has no email.");
        }

        $candidate = Candidate::where('hubspot_id', $hubspotId)
            ->orWhere('email', $email)
            ->first();

        $formationCode = $props['formation_souhaitee_pour_ypareo'] ?? null;

        $formationLabels = $this->getFormationLabels();

        $formationLabel = $formationCode
            ? ($formationLabels[$formationCode] ?? null)
            : null;

        $candidateData = [
            'hubspot_id' => $hubspotId,
            'first_name' => !blank($props['firstname'] ?? null) ? $props['firstname'] : ($candidate ? $candidate->first_name : 'Inconnu'),
            'last_name' => !blank($props['lastname'] ?? null) ? $props['lastname'] : ($candidate ? $candidate->last_name : 'Inconnu'),
            'email' => $email,
            'phone' => !blank($props['phone'] ?? null) ? $props['phone'] : ($candidate ? $candidate->phone : null),
            // formation_souhaitee is corrected with the Ypareo label (source of truth)
            // when available, since the raw HubSpot value does not update if the
            // candidate later changes their desired program.
            'formation_souhaitee' => !blank($formationLabel)
                ? $formationLabel
                : (!blank($props['formation_souhaitee'] ?? null)
                    ? $props['formation_souhaitee']
                    : ($candidate ? $candidate->formation_souhaitee : null)),
            'formation_souhaitee_pour_ypareo' => !blank($formationCode)
                ? $formationCode
                : ($candidate ? $candidate->formation_souhaitee_pour_ypareo : null),
            'score_test_technique' => !blank($props['score_test_technique'] ?? null) ? $props['score_test_technique'] : ($candidate ? $candidate->score_test_technique : null),
            'resultat_test_technique' => !blank($props['resultat_test_technique'] ?? null) ? $props['resultat_test_technique'] : ($candidate ? $candidate->resultat_test_technique : null),
            'date_test_technique' => !blank($props['date_test_technique'] ?? null) ? $props['date_test_technique'] : ($candidate ? $candidate->date_test_technique : null),
            'orientation_proposee' => !blank($props['orientation_proposee'] ?? null) ? $props['orientation_proposee'] : ($candidate ? $candidate->orientation_proposee : null),
        ];

        if ($candidate) {
            if ($candidate->added_by !== 'hubspot') {
                $candidateData['added_by'] = 'hubspot';
            }
            $candidate->update($candidateData);
            $candidate->updateScoreFromSessions();
        } else {
            $candidateData['added_by'] = 'hubspot';
            $candidate = Candidate::create($candidateData);
            $candidate->updateScoreFromSessions();
        }

        // Automatic test session generation
        $ypareoCode = $candidate->formation_souhaitee_pour_ypareo;
        if (!blank($ypareoCode)) {
            $template = \App\Models\TestTemplate::findByYpareoCode($ypareoCode);
            if ($template) {
                // Check if candidate already has a test session
                $hasSession = $candidate->testSessions()->exists();
                if (!$hasSession) {
                    Log::info("Auto-generating test session for HubSpot candidate: {$candidate->email} with template {$template->name}");
                    
                    /** @var \App\Services\TestGeneratorService $generator */
                    $generator = app(\App\Services\TestGeneratorService::class);
                    $session = $generator->generateSession($candidate, $template);
                    
                    // Reset local candidate's technical test fields
                    $candidate->update([
                        'score_test_technique' => null,
                        'resultat_test_technique' => null,
                        'date_test_technique' => null,
                    ]);

                    // Sync the link back to HubSpot immediately
                    $this->updateContact($candidate->email, [
                        'resultat_test_technique' => '',
                        'score_test_technique' => '',
                        'date_test_technique' => '',
                        'orientation_proposee' => '',
                        'lien_test_technique' => route('test.start', $session->token),
                    ]);
                }
            }
        }

        return $candidate;
    }
}