<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai\Gemini;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiPrompt;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponse;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Application\Clock\Clock;
use App\Shared\Infrastructure\Ai\AiProviderClient;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class GeminiAiClient implements AiProviderClient
{
    private const string API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';
    private const float TIMEOUT = 300.0;
    private const float HIGH_TIMEOUT = 600.0;

    public function __construct(
        private HttpClientInterface $httpClient,
        private AiRequestLog $log,
        private Clock $clock,
        private string $apiKey,
        private string $modelLow,
        private string $modelMedium,
        private string $modelHigh,
    ) {}

    public function provider(): AiProvider
    {
        return AiProvider::Gemini;
    }

    public function send(AiPrompt $prompt): AiResponse
    {
        $model = $this->model($prompt->modelTier());

        if ('' === trim($this->apiKey)) {
            throw AiResponseException::providerNotConfigured(AiProvider::Gemini);
        }

        $request = [
            ...$this->systemInstruction($prompt),
            'contents' => [
                ['role' => 'user', 'parts' => [...$this->attachmentParts($prompt), ['text' => $prompt->text()]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $prompt->responseSchema(),
                ...$this->thinkingConfig($model, $prompt->effort()),
            ],
        ];

        $requestedAt = $this->clock->now();
        $this->log->write(AiProvider::Gemini, $model, $prompt, $requestedAt, $request, [], []);

        $start = hrtime(true);

        $response = $this->httpClient->request('POST', str_replace('{model}', $model, self::API_URL), [
            'headers' => ['x-goog-api-key' => $this->apiKey],
            'json' => $request,
            'timeout' => $this->timeout($prompt->effort()),
        ]);

        $status = $response->getStatusCode();

        if ($status >= 400) {
            $body = $response->getContent(false);
            $error = json_decode($body, true);

            $this->log->write(AiProvider::Gemini, $model, $prompt, $requestedAt, $request, is_array($error) ? $error : ['body' => $body], []);

            throw AiResponseException::httpError($model, $status, $this->errorMessage($error, $body));
        }

        $data = $response->toArray(false);
        $usage = $this->usage($data, $model, (int) round((hrtime(true) - $start) / 1_000_000));

        $finishReason = $data['candidates'][0]['finishReason'] ?? $data['promptFeedback']['blockReason'] ?? 'NO_CANDIDATES';
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $result = json_decode(is_string($jsonText) ? $jsonText : '', true);
        $jsonError = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : 'the response is not a JSON object or array';

        $this->log->write(AiProvider::Gemini, $model, $prompt, $requestedAt, $request, $data, is_array($result) ? $result : []);

        if ('STOP' !== $finishReason) {
            throw AiResponseException::incomplete($model, is_string($finishReason) ? $finishReason : 'NO_CANDIDATES', $usage);
        }

        if (!is_array($result)) {
            throw AiResponseException::invalidJson($model, $jsonError, $usage);
        }

        return new AiResponse($result, $usage);
    }

    /** @return array{systemInstruction?: array{parts: list<array{text: string}>}} */
    private function systemInstruction(AiPrompt $prompt): array
    {
        if ('' === trim($prompt->system())) {
            return [];
        }

        return ['systemInstruction' => ['parts' => [['text' => $prompt->system()]]]];
    }

    /** @return list<array<string, mixed>> */
    private function attachmentParts(AiPrompt $prompt): array
    {
        return array_map(fn(AiAttachment $attachment): array => $this->attachmentPart($attachment), $prompt->attachments());
    }

    /** @return array<string, mixed> */
    private function attachmentPart(AiAttachment $attachment): array
    {
        if (!$attachment->isPdf() && !$attachment->isImage()) {
            throw AiResponseException::unsupportedAttachment($attachment->mimeType);
        }

        return ['inlineData' => ['mimeType' => $attachment->mimeType, 'data' => base64_encode($attachment->contents)]];
    }

    /** @param array<mixed> $data */
    private function usage(array $data, string $model, int $durationMs): AiUsage
    {
        $metadata = is_array($data['usageMetadata'] ?? null) ? $data['usageMetadata'] : [];

        return new AiUsage(
            AiProvider::Gemini,
            $model,
            $this->tokens($metadata, 'promptTokenCount'),
            $this->tokens($metadata, 'candidatesTokenCount') + $this->tokens($metadata, 'thoughtsTokenCount'),
            $this->tokens($metadata, 'cachedContentTokenCount'),
            0,
            0.0,
            $durationMs,
        );
    }

    /** @param array<mixed> $metadata */
    private function tokens(array $metadata, string $key): int
    {
        return is_int($metadata[$key] ?? null) ? $metadata[$key] : 0;
    }

    private function errorMessage(mixed $error, string $body): string
    {
        if (is_array($error) && is_array($error['error'] ?? null) && is_string($error['error']['message'] ?? null)) {
            return $error['error']['message'];
        }

        return mb_substr(trim($body), 0, 200);
    }

    private function model(AiModelTier $tier): string
    {
        return match ($tier) {
            AiModelTier::LOW => $this->modelLow,
            AiModelTier::MEDIUM => $this->modelMedium,
            AiModelTier::HIGH => $this->modelHigh,
        };
    }

    private function timeout(AiEffort $effort): float
    {
        return AiEffort::HIGH === $effort ? self::HIGH_TIMEOUT : self::TIMEOUT;
    }

    /** @return array{thinkingConfig?: array{thinkingLevel?: string, thinkingBudget?: int}} */
    private function thinkingConfig(string $model, AiEffort $effort): array
    {
        if (1 !== preg_match('/^gemini-(\d+)(?:\.(\d+))?/', $model, $matches)) {
            return [];
        }

        $major = (int) $matches[1];
        $minor = (int) ($matches[2] ?? 0);
        $isPro = str_contains($model, '-pro');

        if ($major >= 3) {
            return ['thinkingConfig' => ['thinkingLevel' => $this->thinkingLevelName($effort, $this->supportsMedium($major, $minor, $isPro))]];
        }

        if (2 === $major && 5 === $minor) {
            return ['thinkingConfig' => ['thinkingBudget' => match ($effort) {
                AiEffort::LOW => 1024,
                AiEffort::MEDIUM => 8192,
                AiEffort::HIGH => 24576,
            }]];
        }

        return [];
    }

    private function thinkingLevelName(AiEffort $effort, bool $supportsMedium): string
    {
        return match ($effort) {
            AiEffort::LOW => 'low',
            AiEffort::MEDIUM => $supportsMedium ? 'medium' : 'high',
            AiEffort::HIGH => 'high',
        };
    }

    private function supportsMedium(int $major, int $minor, bool $isPro): bool
    {
        return !$isPro || $major > 3 || $minor >= 1;
    }
}
