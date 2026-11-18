<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai\Anthropic;

use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolDefinition;
use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicAiAgentClient;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicPricing;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicUsageReader;
use App\Tests\Unit\Shared\Application\Clock\FixedClock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AnthropicAiAgentClientTest extends TestCase
{
    public function testSendsTheToolsSortedWithTheCachedSystemAndWithoutToolChoice(): void
    {
        $response = $this->response(['content' => [['type' => 'text', 'text' => 'Hola']], 'stop_reason' => 'end_turn']);

        $this->client($response)->step($this->request());

        $body = $this->body($response);

        $this->assertSame('claude-haiku-5-5', $body['model']);
        $this->assertSame(16000, $body['max_tokens']);
        $this->assertSame([['type' => 'text', 'text' => 'Sistema', 'cache_control' => ['type' => 'ephemeral']]], $body['system']);
        $this->assertSame(['list_clients', 'view_task'], array_column($body['tools'], 'name'));
        $this->assertSame(['effort' => 'medium'], $body['output_config']);
        $this->assertArrayNotHasKey('tool_choice', $body);
        $this->assertArrayNotHasKey('thinking', $body);
        $this->assertArrayNotHasKey('temperature', $body);
        $this->assertSame('2026-10-21', $this->header($response, 'anthropic-version'));
    }

    public function testDisablesParallelToolUseWhenRequested(): void
    {
        $response = $this->response(['content' => [], 'stop_reason' => 'end_turn']);

        $this->client($response)->step($this->request(parallelToolCalls: false));

        $this->assertSame(['type' => 'auto', 'disable_parallel_tool_use' => true], $this->body($response)['tool_choice']);
    }

    public function testReadsTheToolCallsOfTheResponse(): void
    {
        $response = $this->response([
            'content' => [
                ['type' => 'text', 'text' => 'Voy a buscar'],
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'list_clients', 'input' => ['name' => 'Acme']],
            ],
            'stop_reason' => 'tool_use',
        ]);

        $step = $this->client($response)->step($this->request());

        $this->assertSame(AiStopReason::ToolUse, $step->stopReason);
        $this->assertSame('Voy a buscar', $step->text());
        $this->assertCount(1, $step->toolCalls());
        $this->assertSame('toolu_1', $step->toolCalls()[0]->id);
        $this->assertSame(['name' => 'Acme'], $step->toolCalls()[0]->input);
    }

    public function testUnknownBlocksAreKeptOpaqueAndSentBackIntact(): void
    {
        $thinking = ['type' => 'thinking', 'thinking' => '…', 'signature' => 'abc'];
        $response = $this->response(['content' => [$thinking, ['type' => 'tool_use', 'id' => 't1', 'name' => 'list_clients', 'input' => []]], 'stop_reason' => 'tool_use']);

        $step = $this->client($response)->step($this->request());

        $this->assertTrue($step->message->blocks[0]->isOpaque());

        $next = $this->response(['content' => [['type' => 'text', 'text' => 'Fin']], 'stop_reason' => 'end_turn']);
        $this->client($next)->step($this->request(messages: [
            AiAgentMessage::userText('Hola'),
            $step->message,
            AiAgentMessage::toolResults([AiAgentBlock::toolResult('t1', '{"total":0}'), AiAgentBlock::toolResult('t2', 'Error', true)]),
        ]));

        $messages = $this->body($next)['messages'];

        $this->assertSame($thinking, $messages[1]['content'][0]);
        $this->assertSame('user', $messages[2]['role']);
        $this->assertSame([
            ['type' => 'tool_result', 'tool_use_id' => 't1', 'content' => '{"total":0}', 'is_error' => false],
            ['type' => 'tool_result', 'tool_use_id' => 't2', 'content' => 'Error', 'is_error' => true, 'cache_control' => ['type' => 'ephemeral']],
        ], $messages[2]['content']);
        $this->assertStringContainsString('"input":{}', (string) $next->getRequestOptions()['body']);
    }

    public function testSendsTheAttachmentsOfAToolResultAndOfAUserMessageAndCachesTheLastBlock(): void
    {
        $response = $this->response(['content' => [['type' => 'text', 'text' => 'Fin']], 'stop_reason' => 'end_turn']);

        $this->client($response)->step($this->request(messages: [
            new AiAgentMessage(AiAgentRole::User, [
                AiAgentBlock::attachment(new AiAttachment('foto.png', 'image/png', 'png-bytes')),
                AiAgentBlock::text('Mira esto'),
            ]),
            AiAgentMessage::toolResults([
                AiAgentBlock::toolResult('t1', 'Fichero', false, [new AiAttachment('manual.pdf', 'application/pdf', '%PDF')]),
                AiAgentBlock::toolResult('t2', 'Sin adjuntos'),
            ]),
        ]));

        $messages = $this->body($response)['messages'];

        $this->assertSame(['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => base64_encode('png-bytes')]], $messages[0]['content'][0]);
        $this->assertSame(['type' => 'text', 'text' => 'Mira esto'], $messages[0]['content'][1]);
        $this->assertSame([
            ['type' => 'text', 'text' => 'Fichero'],
            ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode('%PDF')]],
        ], $messages[1]['content'][0]['content']);
        $this->assertSame('Sin adjuntos', $messages[1]['content'][1]['content']);
        $this->assertSame(['type' => 'ephemeral'], $messages[1]['content'][1]['cache_control']);
        $this->assertArrayNotHasKey('cache_control', $messages[1]['content'][0]);
    }

    public function testMapsTheRefusal(): void
    {
        $response = $this->response(['content' => [], 'stop_reason' => 'refusal']);

        $this->assertSame(AiStopReason::Refusal, $this->client($response)->step($this->request())->stopReason);
    }

    public function testFailsWithTheHttpError(): void
    {
        $response = new MockResponse((string) json_encode(['error' => ['message' => 'Bad tool']]), ['http_code' => 400]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('Bad tool');

        $this->client($response)->step($this->request());
    }

    public function testComputesTheCost(): void
    {
        $response = $this->response([
            'model' => 'claude-haiku-5-5',
            'content' => [['type' => 'text', 'text' => 'Hola']],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 1_000_000, 'output_tokens' => 0],
        ]);

        $usage = $this->client($response)->step($this->request())->usage;

        $this->assertSame(1_000_000, $usage->inputTokens);
        $this->assertSame(0.10, $usage->costUsd);
    }

    /** @param list<AiAgentMessage>|null $messages */
    private function request(bool $parallelToolCalls = true, ?array $messages = null): AiAgentRequest
    {
        return new AiAgentRequest(
            name: 'assistant-query',
            system: 'Sistema',
            tools: [
                new AiToolDefinition('view_task', 'Ver tarea', ['type' => 'object', 'properties' => []]),
                new AiToolDefinition('list_clients', 'Listar', ['type' => 'object', 'properties' => []]),
            ],
            messages: $messages ?? [new AiAgentMessage(AiAgentRole::User, [AiAgentBlock::text('Hola')])],
            modelTier: AiModelTier::LOW,
            effort: AiEffort::MEDIUM,
            parallelToolCalls: $parallelToolCalls,
        );
    }

    /** @param array<mixed> $body */
    private function response(array $body): MockResponse
    {
        return new MockResponse((string) json_encode($body));
    }

    /** @return array<mixed> */
    private function body(MockResponse $response): array
    {
        $body = json_decode((string) $response->getRequestOptions()['body'], true);

        $this->assertIsArray($body);

        return $body;
    }

    private function header(MockResponse $response, string $name): ?string
    {
        foreach ($response->getRequestOptions()['headers'] as $header) {
            if (str_starts_with(strtolower($header), $name . ':')) {
                return trim(substr($header, strlen($name) + 1));
            }
        }

        return null;
    }

    private function client(MockResponse $response): AnthropicAiAgentClient
    {
        return new AnthropicAiAgentClient(new MockHttpClient($response), new AnthropicUsageReader(new AnthropicPricing()), new AiRequestLog(false, ''), new FixedClock(), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5');
    }
}
