<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiHttpClient implements GeminiClient
{
    private const string API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    /** @return array<mixed> */
    public function send(GeminiPrompt $prompt): array
    {
        $response = $this->httpClient->request('POST', $this->getApiUrl(), [
            'query' => ['key' => $this->apiKey],
            'json' => [
                'contents' => [
                    ['parts' => [['text' => $prompt->text()]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $prompt->responseSchema(),
                    'temperature' => $prompt->temperature(),
                ],
            ],
        ]);

        $data = $response->toArray();
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

        return json_decode($jsonText, true) ?? [];
    }

    private function getApiUrl(): string
    {
        return str_replace('{model}', $this->model, self::API_URL);
    }
}
