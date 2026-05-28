<?php

namespace App\Services;

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
     * @param string $sinceDate The date from which to search (default "2026-01-01T00:00:00Z")
     * @return array|null
     */
    public function searchCandidates(string|array $statusValue = ["145", "162"], string $sinceDate = "2026-01-01T00:00:00Z"): ?array
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
                    ],
                    [
                        "propertyName" => "createdate",
                        "operator" => "GTE",
                        "value" => $sinceDate
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
}
