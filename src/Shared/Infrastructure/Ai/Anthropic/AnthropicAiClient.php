<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai\Anthropic;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiPrompt;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponse;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Clock\Clock;
use App\Shared\Infrastructure\Ai\AiProviderClient;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class AnthropicAiClient implements AiProviderClient
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string API_VERSION = '2026-10-21';
    private const int MAX_TOKENS = 16000;
    private const float TIMEOUT = 300.0;

    public function __construct(
        private HttpClientInterface $httpClient,
        private JsonSchemaNormalizer $schemaNormalizer,
        private AnthropicUsageReader $usageReader,
        private AiRequestLog $log,
        private Clock $clock,
        private string $apiKey,
        private string $modelLow,
        private string $modelMedium,
        private string $modelHigh,
    ) {}

    public function provider(): AiProvider
    {
        return AiProvider::Anthropic;
    }

    public function send(AiPrompt $prompt): AiResponse
    {
        $model = $this->model($prompt->modelTier());

        if ('' === trim($this->apiKey)) {
            throw AiResponseException::providerNotConfigured(AiProvider::Anthropic);
        }

        $request = [
            'model' => $model,
            'max_tokens' => self::MAX_TOKENS,
            'system' => [['type' => 'text', 'text' => $prompt->system(), 'cache_control' => ['type' => 'ephemeral']]],
            'messages' => [['role' => 'user', 'content' => [...$this->attachmentBlocks($prompt), ['type' => 'text', 'text' => $prompt->text()]]]],
            'output_config' => [
                'effort' => $prompt->effort()->value,
                'format' => ['type' => 'json_schema', 'schema' => $this->schemaNormalizer->normalize($prompt->responseSchema())],
            ],
        ];

        $requestedAt = $this->clock->now();
        $this->log->write(AiProvider::Anthropic, $model, $prompt, $requestedAt, $request, [], []);

        $start = hrtime(true);

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ],
            'json' => $request,
            'timeout' => self::TIMEOUT,
            'max_duration' => self::TIMEOUT,
        ]);

        $status = $response->getStatusCode();

        if ($status >= 400) {
            $body = $response->getContent(false);
            $error = json_decode($body, true);

            $this->log->write(AiProvider::Anthropic, $model, $prompt, $requestedAt, $request, is_array($error) ? $error : ['body' => $body], []);

            throw AiResponseException::httpError($model, $status, $this->errorMessage($error, $body));
        }

        $data = $response->toArray(false);
        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);
        $usage = $this->usageReader->read($data, $model, $durationMs);

        $stopReason = is_string($data['stop_reason'] ?? null) ? $data['stop_reason'] : 'NO_STOP_REASON';
        $result = json_decode($this->text($data), true);
        $jsonError = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : 'the response is not a JSON object or array';

        $this->log->write(AiProvider::Anthropic, $model, $prompt, $requestedAt, $request, $data, is_array($result) ? $result : []);

        if ('end_turn' !== $stopReason) {
            if ('refusal' === $stopReason) {
                $category = $data['stop_details']['category'] ?? null;
                $stopReason = sprintf('refusal, category %s', is_string($category) ? $category : 'unknown');
            }

            throw AiResponseException::incomplete($model, $stopReason, $usage);
        }

        if (!is_array($result)) {
            throw AiResponseException::invalidJson($model, $jsonError, $usage);
        }

        return new AiResponse($result, $usage, $stopReason);
    }

    /** @return list<array<string, mixed>> */
    private function attachmentBlocks(AiPrompt $prompt): array
    {
        return array_map(fn(AiAttachment $attachment): array => $this->attachmentBlock($attachment), $prompt->attachments());
    }

    /** @return array<string, mixed> */
    private function attachmentBlock(AiAttachment $attachment): array
    {
        if ($attachment->isPdf()) {
            return ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($attachment->contents)]];
        }

        if ($attachment->isImage()) {
            return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $attachment->mimeType, 'data' => base64_encode($attachment->contents)]];
        }

        throw AiResponseException::unsupportedAttachment($attachment->mimeType);
    }

    private function errorMessage(mixed $error, string $body): string
    {
        if (is_array($error) && is_array($error['error'] ?? null) && is_string($error['error']['message'] ?? null)) {
            return $error['error']['message'];
        }

        return mb_substr(trim($body), 0, 200);
    }

    /** @param array<mixed> $data */
    private function text(array $data): string
    {
        $content = is_array($data['content'] ?? null) ? $data['content'] : [];

        foreach ($content as $block) {
            if (is_array($block) && 'text' === ($block['type'] ?? null)) {
                return is_string($block['text'] ?? null) ? $block['text'] : '';
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
}
