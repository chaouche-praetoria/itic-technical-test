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
     * @param string $statusValue The value for statut_ypareo_candidat__ne_pas_modifier_ (default "145")
     * @param string $sinceDate The date from which to search (default "2026-01-01T00:00:00Z")
     * @return array|null
     */
    public function searchCandidates(string $statusValue = "145", string $sinceDate = "2026-01-01T00:00:00Z"): ?array
    {
        if (!$this->accessToken) {
            Log::error("HubSpot Access Token is missing in configuration.");
            return null;
        }

        $payload = [
            "filterGroups" => [
                [
                    "filters" => [
                        [
                            "propertyName" => "statut_ypareo_candidat__ne_pas_modifier_",
                            "operator" => "EQ",
                            "value" => $statusValue
                        ],
                        [
                            "propertyName" => "createdate",
                            "operator" => "GTE",
                            "value" => $sinceDate
                        ]
                    ]
                ]
            ],
            "properties" => [
                "email",
                "firstname",
                "lastname",
                "phone",
                "formation_souhaitee",
                "formation_souhaitee_pour_ypareo",
                "score_test_entretien"
            ],
            "limit" => 100
        ];

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/search", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("HubSpot API Search Error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("HubSpot Service Exception: " . $e->getMessage());
            return null;
        }
    }
}
