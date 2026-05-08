<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiHttpClient implements GeminiClient
{
    private const string API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';

    private \DateTimeImmutable $requestedAt;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly bool $logEnabled = false,
        private readonly string $logDir = '',
    ) {}

    /** @return array<mixed> */
    public function send(GeminiPrompt $prompt): array
    {
        $request = [
            'contents' => [
                ['parts' => [['text' => $prompt->text()]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $prompt->responseSchema(),
                'temperature' => $prompt->temperature(),
            ],
        ];

        if ($this->logEnabled) {
            $this->requestedAt = new \DateTimeImmutable();
            $this->writeLog($prompt, $request, [], []);
        }

        $response = $this->httpClient->request('POST', $this->getApiUrl(), [
            'query' => ['key' => $this->apiKey],
            'json' => $request,
        ]);

        $data = $response->toArray();
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
        $result = json_decode($jsonText, true) ?? [];

        if ($this->logEnabled) {
            $this->writeLog($prompt, $request, $data, $result);
        }

        return $result;
    }

    private function getApiUrl(): string
    {
        return str_replace('{model}', $this->model, self::API_URL);
    }

    /**
     * @param array<mixed> $request
     * @param array<mixed> $rawResponse
     * @param array<mixed> $parsedResult
     */
    private function writeLog(GeminiPrompt $prompt, array $request, array $rawResponse, array $parsedResult): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }

        $timestamp = $this->requestedAt->format('Y-m-d_H-i-s-u');
        $promptClass = (new \ReflectionClass($prompt))->getShortName();
        $filename = sprintf('%s/%s_%s.json', $this->logDir, $timestamp, $promptClass);

        $log = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'model' => $this->model,
            'prompt_class' => $prompt::class,
            'request' => [
                'text' => $prompt->text(),
                'temperature' => $prompt->temperature(),
                'responseSchema' => $prompt->responseSchema(),
            ],
            'raw_response' => $rawResponse,
            'parsed_result' => $parsedResult,
        ];

        file_put_contents($filename, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
