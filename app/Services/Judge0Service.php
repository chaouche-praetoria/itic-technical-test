<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Judge0Service
{
    private string $baseUrl;
    private ?string $apiKey;

    // Language IDs in Judge0
    const LANGUAGES = [
        'javascript' => 93,
        'python' => 71,
        'php' => 68,
        'java' => 62,
        'c' => 50,
        'cpp' => 54,
        'csharp' => 51,
        'go' => 60,
        'ruby' => 72,
        'typescript' => 94,
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.judge0.url', 'https://judge0-ce.p.rapidapi.com');
        $this->apiKey = config('services.judge0.api_key');
    }

    public function execute(string $code, string $language, string $unitTests): array
    {
        $languageId = self::LANGUAGES[$language] ?? 93;
        $fullCode = $this->wrapWithTests($code, $unitTests, $language);

        try {
            $headers = ['Content-Type' => 'application/json'];
            if ($this->apiKey) {
                $headers['X-RapidAPI-Key'] = $this->apiKey;
                $headers['X-RapidAPI-Host'] = 'judge0-ce.p.rapidapi.com';
            }

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->post("{$this->baseUrl}/submissions?wait=true", [
                    'source_code' => base64_encode($fullCode),
                    'language_id' => $languageId,
                    'cpu_time_limit' => 5,
                    'memory_limit' => 128000,
                    'encode_fields' => 'stdout,stderr,compile_output',
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Judge0 API error', 'output' => ''];
            }

            $result = $response->json();
            $stdout = base64_decode($result['stdout'] ?? '');
            $stderr = base64_decode($result['stderr'] ?? '');
            $compile = base64_decode($result['compile_output'] ?? '');

            $passed = $this->parseTestResults($stdout);

            return [
                'success' => $passed['all_passed'],
                'passed' => $passed['passed'],
                'total' => $passed['total'],
                'output' => $stdout,
                'error' => $stderr ?: $compile,
                'status' => $result['status']['description'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            Log::error("Judge0 execution failed: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage(), 'output' => ''];
        }
    }

    private function wrapWithTests(string $code, string $tests, string $language): string
    {
        return $code . "\n" . $tests;
    }

    private function parseTestResults(string $output): array
    {
        preg_match_all('/PASS|FAIL/', $output, $matches);
        $results = $matches[0];
        $passed = count(array_filter($results, fn($r) => $r === 'PASS'));
        $total = count($results) ?: 1;

        return [
            'passed' => $passed,
            'total' => $total,
            'all_passed' => $passed === $total && $total > 0,
        ];
    }
}
