<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Anthropic;

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
class AnthropicHttpClient implements AiClient
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string API_VERSION = '2026-10-21';
    private const int MAX_TOKENS = 32000;
    private const float TIMEOUT = 300.0;
    private const float HIGH_TIMEOUT = 600.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly JsonSchemaNormalizer $schemaNormalizer,
        private readonly AiRequestLog $log,
        private readonly string $apiKey,
        private readonly string $modelLow,
        private readonly string $modelMedium,
        private readonly string $modelHigh,
    ) {}

    /** @return array<mixed> */
    public function send(AiPrompt $prompt): array
    {
        $model = $this->model($prompt->modelTier());

        $request = [
            'model' => $model,
            'max_tokens' => self::MAX_TOKENS,
            'messages' => [['role' => 'user', 'content' => $prompt->text()]],
            'output_config' => [
                'effort' => $this->effort($prompt->thinkingLevel()),
                'format' => ['type' => 'json_schema', 'schema' => $this->schemaNormalizer->normalize($prompt->responseSchema())],
            ],
        ];

        $requestedAt = new \DateTimeImmutable();

        if ($this->log->enabled()) {
            $this->log->write(AiProvider::Anthropic, $model, $prompt, $requestedAt, $request, [], []);
        }

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => ['x-api-key' => $this->apiKey, 'anthropic-version' => self::API_VERSION],
            'json' => $request,
            'timeout' => $this->timeout($prompt->thinkingLevel()),
        ]);

        $status = $response->getStatusCode();

        if ($status >= 400) {
            $body = $response->getContent(false);
            $error = json_decode($body, true);

            if ($this->log->enabled()) {
                $this->log->write(AiProvider::Anthropic, $model, $prompt, $requestedAt, $request, is_array($error) ? $error : ['body' => $body], []);
            }

            throw AiResponseException::httpError($model, $status, is_array($error) ? (string) ($error['error']['message'] ?? '') : mb_substr(trim($body), 0, 200));
        }

        $data = $response->toArray(false);

        $stopReason = (string) ($data['stop_reason'] ?? 'NO_STOP_REASON');
        $result = json_decode($this->text($data), true);
        $jsonError = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : 'the response is not a JSON object or array';

        if ($this->log->enabled()) {
            $this->log->write(AiProvider::Anthropic, $model, $prompt, $requestedAt, $request, $data, is_array($result) ? $result : []);
        }

        if ('end_turn' !== $stopReason) {
            $usage = sprintf('%d output tokens', (int) ($data['usage']['output_tokens'] ?? 0));

            if ('refusal' === $stopReason) {
                $usage .= sprintf(' (category %s)', (string) ($data['stop_details']['category'] ?? 'unknown'));
            }

            throw AiResponseException::incomplete($model, $stopReason, $usage);
        }

        if (!is_array($result)) {
            throw AiResponseException::invalidJson($model, $jsonError);
        }

        return $result;
    }

    /**
     * @param array<mixed> $data
     */
    private function text(array $data): string
    {
        foreach ($data['content'] ?? [] as $block) {
            if ('text' === ($block['type'] ?? null)) {
                return (string) ($block['text'] ?? '');
            }
        }

        return '';
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

    private function effort(AiThinkingLevel $level): string
    {
        return match ($level) {
            AiThinkingLevel::MINIMAL, AiThinkingLevel::LOW => 'low',
            AiThinkingLevel::MEDIUM => 'medium',
            AiThinkingLevel::HIGH => 'high',
        };
    }
}
