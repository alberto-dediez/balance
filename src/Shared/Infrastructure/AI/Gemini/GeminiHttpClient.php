<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

use App\Shared\Infrastructure\AI\AiClient;
use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiPrompt;
use App\Shared\Infrastructure\AI\AiProvider;
use App\Shared\Infrastructure\AI\AiRequestLog;
use App\Shared\Infrastructure\AI\AiResponseException;
use App\Shared\Infrastructure\AI\AiThinkingLevel;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
class GeminiHttpClient implements AiClient
{
    private const string API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';
    private const float TIMEOUT = 300.0;
    private const float HIGH_TIMEOUT = 600.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $modelLow,
        private readonly string $modelMedium,
        private readonly string $modelHigh,
        private readonly AiRequestLog $log,
    ) {}

    /** @return array<mixed> */
    public function send(AiPrompt $prompt): array
    {
        $model = $this->model($prompt->modelTier());

        $request = [
            'contents' => [
                ['parts' => [['text' => $prompt->text()]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $prompt->responseSchema(),
                ...$this->thinkingConfig($model, $prompt->thinkingLevel()),
            ],
        ];

        $requestedAt = new \DateTimeImmutable();

        if ($this->log->enabled()) {
            $this->log->write(AiProvider::Gemini, $model, $prompt, $requestedAt, $request, [], []);
        }

        $response = $this->httpClient->request('POST', str_replace('{model}', $model, self::API_URL), [
            'headers' => ['x-goog-api-key' => $this->apiKey],
            'json' => $request,
            'timeout' => $this->timeout($prompt->thinkingLevel()),
        ]);

        $status = $response->getStatusCode();

        if ($status >= 400) {
            $body = $response->getContent(false);
            $error = json_decode($body, true);

            if ($this->log->enabled()) {
                $this->log->write(AiProvider::Gemini, $model, $prompt, $requestedAt, $request, is_array($error) ? $error : ['body' => $body], []);
            }

            throw AiResponseException::httpError($model, $status, is_array($error) ? (string) ($error['error']['message'] ?? '') : mb_substr(trim($body), 0, 200));
        }

        $data = $response->toArray(false);

        $finishReason = $data['candidates'][0]['finishReason'] ?? $data['promptFeedback']['blockReason'] ?? 'NO_CANDIDATES';
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $result = json_decode($jsonText, true);
        $jsonError = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : 'the response is not a JSON object or array';

        if ($this->log->enabled()) {
            $this->log->write(AiProvider::Gemini, $model, $prompt, $requestedAt, $request, $data, is_array($result) ? $result : []);
        }

        if ('STOP' !== $finishReason) {
            throw AiResponseException::incomplete($model, (string) $finishReason, sprintf('%d output tokens and %d thinking tokens', (int) ($data['usageMetadata']['candidatesTokenCount'] ?? 0), (int) ($data['usageMetadata']['thoughtsTokenCount'] ?? 0)));
        }

        if (!is_array($result)) {
            throw AiResponseException::invalidJson($model, $jsonError);
        }

        return $result;
    }

    private function model(AiModelTier $tier): string
    {
        return match ($tier) {
            AiModelTier::LOW => $this->modelLow,
            AiModelTier::MEDIUM => $this->modelMedium,
            AiModelTier::HIGH => $this->modelHigh,
        };
    }

    private function timeout(AiThinkingLevel $level): float
    {
        return AiThinkingLevel::HIGH === $level ? self::HIGH_TIMEOUT : self::TIMEOUT;
    }

    /**
     * @return array{thinkingConfig?: array{thinkingLevel?: string, thinkingBudget?: int}}
     */
    private function thinkingConfig(string $model, AiThinkingLevel $level): array
    {
        if (1 !== preg_match('/^gemini-(\d+)(?:\.(\d+))?/', $model, $matches)) {
            return [];
        }

        $major = (int) $matches[1];
        $minor = (int) ($matches[2] ?? 0);
        $isPro = str_contains($model, '-pro');

        if ($major >= 3) {
            return ['thinkingConfig' => ['thinkingLevel' => $this->thinkingLevelName($level, $this->supportsMinimal($major, $minor, $isPro), $this->supportsMedium($major, $minor, $isPro))]];
        }

        if (2 === $major && 5 === $minor) {
            return ['thinkingConfig' => ['thinkingBudget' => match ($level) {
                AiThinkingLevel::MINIMAL => $isPro ? 128 : 0,
                AiThinkingLevel::LOW => 1024,
                AiThinkingLevel::MEDIUM => 8192,
                AiThinkingLevel::HIGH => 24576,
            }]];
        }

        return [];
    }

    private function thinkingLevelName(AiThinkingLevel $level, bool $supportsMinimal, bool $supportsMedium): string
    {
        return match ($level) {
            AiThinkingLevel::MINIMAL => $supportsMinimal ? 'minimal' : 'low',
            AiThinkingLevel::LOW => 'low',
            AiThinkingLevel::MEDIUM => $supportsMedium ? 'medium' : 'high',
            AiThinkingLevel::HIGH => 'high',
        };
    }

    private function supportsMinimal(int $major, int $minor, bool $isPro): bool
    {
        return !$isPro && 3 === $major && $minor <= 6;
    }

    private function supportsMedium(int $major, int $minor, bool $isPro): bool
    {
        return !$isPro || $major > 3 || $minor >= 1;
    }
}
