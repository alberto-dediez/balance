<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai\Anthropic;

use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolDefinition;
use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Clock\Clock;
use App\Shared\Infrastructure\Ai\AiAgentProviderClient;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class AnthropicAiAgentClient implements AiAgentProviderClient
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string API_VERSION = '2026-10-21';
    private const int MAX_TOKENS = 16000;
    private const float TIMEOUT = 300.0;

    public function __construct(
        private HttpClientInterface $httpClient,
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

    public function step(AiAgentRequest $request): AiAgentStep
    {
        $model = $this->model($request->modelTier);

        if ('' === trim($this->apiKey)) {
            throw AiResponseException::providerNotConfigured(AiProvider::Anthropic);
        }

        $body = $this->body($request, $model);
        $requestedAt = $this->clock->now();
        $start = hrtime(true);

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => self::TIMEOUT,
            'max_duration' => self::TIMEOUT,
        ]);

        $status = $response->getStatusCode();

        if ($status >= 400) {
            $content = $response->getContent(false);
            $error = json_decode($content, true);

            $this->log->writeAgent(AiProvider::Anthropic, $model, $request->name, $requestedAt, $body, is_array($error) ? $error : ['body' => $content]);

            throw AiResponseException::httpError($model, $status, $this->errorMessage($error, $content));
        }

        $data = $response->toArray(false);
        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

        $this->log->writeAgent(AiProvider::Anthropic, $model, $request->name, $requestedAt, $body, $data);

        return new AiAgentStep(
            new AiAgentMessage(AiAgentRole::Assistant, $this->blocks($data)),
            $this->usageReader->read($data, $model, $durationMs),
            $this->stopReason($data['stop_reason'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    private function body(AiAgentRequest $request, string $model): array
    {
        $tools = $request->tools;
        usort($tools, static fn(AiToolDefinition $a, AiToolDefinition $b): int => strcmp($a->name, $b->name));

        $body = [
            'model' => $model,
            'max_tokens' => self::MAX_TOKENS,
            'system' => [['type' => 'text', 'text' => $request->system, 'cache_control' => ['type' => 'ephemeral']]],
            'tools' => array_map(fn(AiToolDefinition $tool): array => [
                'name' => $tool->name,
                'description' => $tool->description,
                'input_schema' => $this->schema($tool->inputSchema),
            ], $tools),
            'messages' => array_map(fn(AiAgentMessage $message): array => [
                'role' => $message->role->value,
                'content' => array_map(fn(AiAgentBlock $block): array => $this->block($block), $message->blocks),
            ], $request->messages),
            'output_config' => ['effort' => $request->effort->value],
        ];

        $body['messages'] = $this->cacheLastBlock($body['messages']);

        if (!$request->parallelToolCalls) {
            $body['tool_choice'] = ['type' => 'auto', 'disable_parallel_tool_use' => true];
        }

        return $body;
    }

    /**
     * @param array<mixed> $messages
     *
     * @return array<mixed>
     */
    private function cacheLastBlock(array $messages): array
    {
        $lastMessage = array_key_last($messages);

        if (null === $lastMessage || !is_array($messages[$lastMessage]['content'] ?? null)) {
            return $messages;
        }

        $lastBlock = array_key_last($messages[$lastMessage]['content']);

        if (null !== $lastBlock) {
            $messages[$lastMessage]['content'][$lastBlock]['cache_control'] = ['type' => 'ephemeral'];
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function schema(array $schema): array
    {
        if (isset($schema['properties']) && [] === $schema['properties']) {
            $schema['properties'] = new \stdClass();
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    private function block(AiAgentBlock $block): array
    {
        return match ($block->type) {
            AiAgentBlock::TEXT => ['type' => 'text', 'text' => $block->text],
            AiAgentBlock::TOOL_USE => ['type' => 'tool_use', 'id' => $block->id, 'name' => $block->name, 'input' => [] === $block->input ? new \stdClass() : $block->input],
            AiAgentBlock::TOOL_RESULT => ['type' => 'tool_result', 'tool_use_id' => $block->id, 'content' => $this->toolResultContent($block), 'is_error' => $block->isError],
            AiAgentBlock::ATTACHMENT => $block->attachment instanceof AiAttachment ? $this->attachmentBlock($block->attachment) : $block->raw,
            default => $block->raw,
        };
    }

    /** @return string|list<array<string, mixed>> */
    private function toolResultContent(AiAgentBlock $block): string|array
    {
        if ([] === $block->attachments) {
            return $block->text;
        }

        return [
            ['type' => 'text', 'text' => $block->text],
            ...array_map(fn(AiAttachment $attachment): array => $this->attachmentBlock($attachment), $block->attachments),
        ];
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

    /**
     * @param array<mixed> $data
     *
     * @return list<AiAgentBlock>
     */
    private function blocks(array $data): array
    {
        $content = is_array($data['content'] ?? null) ? $data['content'] : [];
        $blocks = [];

        foreach ($content as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;

            if ('text' === $type) {
                $blocks[] = AiAgentBlock::text(is_string($block['text'] ?? null) ? $block['text'] : '');

                continue;
            }

            if ('tool_use' === $type) {
                /** @var array<string, mixed> $input */
                $input = is_array($block['input'] ?? null) ? $block['input'] : [];
                $blocks[] = AiAgentBlock::toolUse(
                    is_string($block['id'] ?? null) ? $block['id'] : '',
                    is_string($block['name'] ?? null) ? $block['name'] : '',
                    $input,
                );

                continue;
            }

            /* @var array<string, mixed> $block */
            $blocks[] = AiAgentBlock::opaque($block);
        }

        return $blocks;
    }

    private function stopReason(mixed $stopReason): AiStopReason
    {
        return match ($stopReason) {
            'end_turn' => AiStopReason::EndTurn,
            'tool_use' => AiStopReason::ToolUse,
            'max_tokens' => AiStopReason::MaxTokens,
            'refusal' => AiStopReason::Refusal,
            default => AiStopReason::Other,
        };
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
}
