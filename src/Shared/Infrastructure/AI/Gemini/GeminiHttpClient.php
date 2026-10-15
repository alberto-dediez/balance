<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiHttpClient implements GeminiClient
{
    private const string API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';
    private const float TIMEOUT = 300.0;

    private \DateTimeImmutable $requestedAt;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $modelLow,
        private readonly string $modelMedium,
        private readonly string $modelHigh,
        private readonly bool $logEnabled = false,
        private readonly string $logDir = '',
    ) {}

    /** @return array<mixed> */
    public function send(GeminiPrompt $prompt): array
    {
        $model = $this->model($prompt->modelTier());

        $request = [
            'contents' => [
                ['parts' => [['text' => $prompt->text()]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $prompt->responseSchema(),
                'temperature' => $prompt->temperature(),
                ...$this->thinkingConfig($model, $prompt->thinkingLevel()),
            ],
        ];

        if ($this->logEnabled) {
            $this->requestedAt = new \DateTimeImmutable();
            $this->writeLog($prompt, $request, [], []);
        }

        $response = $this->httpClient->request('POST', str_replace('{model}', $model, self::API_URL), [
            'headers' => ['x-goog-api-key' => $this->apiKey],
            'json' => $request,
            'timeout' => self::TIMEOUT,
        ]);

        $data = $response->toArray();
        $finishReason = $data['candidates'][0]['finishReason'] ?? $data['promptFeedback']['blockReason'] ?? 'NO_CANDIDATES';
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $result = json_decode($jsonText, true);
        $jsonError = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : 'the response is not a JSON object or array';

        if ($this->logEnabled) {
            $this->writeLog($prompt, $request, $data, is_array($result) ? $result : []);
        }

        if ('STOP' !== $finishReason) {
            throw GeminiResponseException::incomplete($model, (string) $finishReason, (int) ($data['usageMetadata']['candidatesTokenCount'] ?? 0), (int) ($data['usageMetadata']['thoughtsTokenCount'] ?? 0));
        }

        if (!is_array($result)) {
            throw GeminiResponseException::invalidJson($model, $jsonError);
        }

        return $result;
    }

    private function model(GeminiModelTier $tier): string
    {
        return match ($tier) {
            GeminiModelTier::LOW => $this->modelLow,
            GeminiModelTier::MEDIUM => $this->modelMedium,
            GeminiModelTier::HIGH => $this->modelHigh,
        };
    }

    /**
     * @return array{thinkingConfig?: array{thinkingLevel?: string, thinkingBudget?: int}}
     */
    private function thinkingConfig(string $model, GeminiThinkingLevel $level): array
    {
        if (1 !== preg_match('/^gemini-(\d+)(?:\.(\d+))?/', $model, $matches)) {
            return [];
        }

        $major = (int) $matches[1];
        $minor = (int) ($matches[2] ?? 0);
        $isPro = str_contains($model, '-pro');

        if ($major >= 3) {
            return ['thinkingConfig' => ['thinkingLevel' => match ($level) {
                GeminiThinkingLevel::MINIMAL => $isPro ? 'low' : 'minimal',
                GeminiThinkingLevel::LOW => 'low',
                GeminiThinkingLevel::MEDIUM => $isPro ? 'high' : 'medium',
                GeminiThinkingLevel::HIGH => 'high',
            }]];
        }

        if (2 === $major && 5 === $minor) {
            return ['thinkingConfig' => ['thinkingBudget' => match ($level) {
                GeminiThinkingLevel::MINIMAL => $isPro ? 128 : 0,
                GeminiThinkingLevel::LOW => 1024,
                GeminiThinkingLevel::MEDIUM => 8192,
                GeminiThinkingLevel::HIGH => 24576,
            }]];
        }

        return [];
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

        $tier = $prompt->modelTier();
        $model = $this->model($tier);

        $log = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'model' => $model,
            'model_tier' => $tier->name,
            'prompt_class' => $prompt::class,
            'request' => [
                'text' => $prompt->text(),
                'temperature' => $prompt->temperature(),
                'thinkingLevel' => $prompt->thinkingLevel()->name,
                'responseSchema' => $prompt->responseSchema(),
            ],
            'raw_response' => $rawResponse,
            'parsed_result' => $parsedResult,
        ];

        file_put_contents($filename, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
