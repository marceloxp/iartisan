<?php

namespace Marceloxp\Iartisan\Services;

use GuzzleHttp\Client;

class GeminiClient
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;

    const URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->client = new Client(['timeout' => 30.0]);
    }

    public function generate(string $prompt): string
    {
        $url = sprintf(self::URL_TEMPLATE, $this->model);

        $payload = [
            "system_instruction" => [
                "parts" => [
                    [
                        "text" => "You are a PHP Laravel 12 expert. The only subject is Laravel artisan commands. Please answer with only the requested command to help the programmer run it in their terminal."
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

        // tenta extrair o texto padrão (vários caminhos possíveis)
        $text = $this->extractText($data) ?? $body;

        // se a resposta for um JSON string como {"command":"..."}
        $maybe = json_decode($text, true);
        if (is_array($maybe) && isset($maybe['command'])) {
            return trim($maybe['command']);
        }

        // procura por "php artisan ..." na resposta
        if (preg_match('/php artisan[^\r\n]*/i', $text, $m)) {
            return trim($m[0]);
        }

        // fallback: primeira linha não vazia
        $lines = preg_split("/\r\n|\n|\r/", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $line = preg_replace('/^Comando:\s*/i', '', $line);
            $line = trim($line, "` \t");
            return $line;
        }

        throw new \RuntimeException('Não foi possível extrair comando da resposta do Gemini.');
    }

    protected function extractText($data)
    {
        if (!is_array($data)) return null;
        // tentativas em caminhos comuns
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

        // busca recursiva por chave 'text'
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
