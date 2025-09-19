<?php

namespace Marceloxp\Iartisan\Services;

use GuzzleHttp\Client;
use Marceloxp\Iartisan\Config\ConfigManager;

class GeminiClient
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;

    const URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $config = new ConfigManager();
        $this->model = $config->get('gemini_model', 'gemini-2.5-flash');
        $this->client = new Client(['timeout' => 30.0]);
    }

    public function generate(string $prompt, ?string $filamentVersion = null): string
    {
        $url = sprintf(self::URL_TEMPLATE, $this->model);

        $systemInstruction = 'You are a PHP Laravel 12 expert. The only subject is Laravel artisan commands. Please answer with only the requested command to help the programmer run it in their terminal.';
        if ($filamentVersion === '3' || $filamentVersion === '4') {
            $systemInstruction = "You are a PHP Laravel 12 and Filament {$filamentVersion} expert. The only subject is Laravel artisan commands, including those related to Filament {$filamentVersion}. Please answer with only the requested command to help the programmer run it in their terminal.";
        }

        $payload = [
            "system_instruction" => [
                "parts" => [
                    [
                        "text" => $systemInstruction
                    ]
                ]
            ],
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => [
                "thinkingConfig" => ["thinkingBudget" => 0],
                "responseMimeType" => "application/json",
                "responseSchema" => [
                    "type" => "object",
                    "properties" => [
                        "command" => ["type" => "string"]
                    ],
                    "propertyOrdering" => ["command"]
                ]
            ]
        ];

        $response = $this->client->post($url, [
            'headers' => [
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        // Try to extract the text (multiple possible paths)
        $text = $this->extractText($data) ?? $body;

        // If response is a JSON string like {"command":"..."}
        $maybe = json_decode($text, true);
        if (is_array($maybe) && isset($maybe['command'])) {
            return trim($maybe['command']);
        }

        // Look for "php artisan ..." in the response
        if (preg_match('/php artisan[^\r\n]*/i', $text, $m)) {
            return trim($m[0]);
        }

        // Fallback: first non-empty line
        $lines = preg_split("/\r\n|\n|\r/", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $line = preg_replace('/^Command:\s*/i', '', $line);
            $line = trim($line, "` \t");
            return $line;
        }

        throw new \RuntimeException('Could not extract command from Gemini response.');
    }

    protected function extractText($data)
    {
        if (!is_array($data)) return null;
        // Try common paths
        $candidates = [
            ['candidates', 0, 'content', 0, 'text'],
            ['output', 0, 'content', 0, 'text'],
            ['responses', 0, 'candidates', 0, 'content', 0, 'text'],
            ['items', 0, 'text']
        ];
        foreach ($candidates as $path) {
            $v = $data;
            foreach ($path as $k) {
                if (is_array($v) && array_key_exists($k, $v)) {
                    $v = $v[$k];
                } else {
                    $v = null;
                    break;
                }
            }
            if (is_string($v) && $v !== '') return $v;
        }

        // Recursive search for 'text' key
        return $this->findFirstKey($data, 'text');
    }

    protected function findFirstKey($arr, $key)
    {
        foreach ($arr as $k => $v) {
            if ($k === $key && is_string($v)) return $v;
            if (is_array($v)) {
                $found = $this->findFirstKey($v, $key);
                if ($found) return $found;
            }
        }
        return null;
    }
}