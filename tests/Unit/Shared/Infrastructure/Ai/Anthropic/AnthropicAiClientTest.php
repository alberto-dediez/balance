<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai\Anthropic;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicAiClient;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicPricing;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicUsageReader;
use App\Shared\Infrastructure\Ai\Anthropic\JsonSchemaNormalizer;
use App\Tests\Unit\Shared\Application\Clock\FixedClock;
use App\Tests\Unit\Shared\Infrastructure\Ai\FakeAiPrompt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AnthropicAiClientTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        $this->logDir = sprintf('%s/ai-logs-%s', sys_get_temp_dir(), uniqid());
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDir . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->logDir)) {
            rmdir($this->logDir);
        }
    }

    /**
     * @dataProvider models
     */
    public function testSendsTheModelOfTheTier(AiModelTier $tier, string $expectedModel): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt(tier: $tier));

        $this->assertSame($expectedModel, $this->request($response)['model']);
        $this->assertSame('https://api.anthropic.com/v1/messages', $response->getRequestUrl());
    }

    /** @return iterable<string, array{AiModelTier, string}> */
    public static function models(): iterable
    {
        yield 'low' => [AiModelTier::LOW, 'claude-haiku-5-5'];
        yield 'medium' => [AiModelTier::MEDIUM, 'claude-sonnet-5-5'];
        yield 'high' => [AiModelTier::HIGH, 'claude-opus-5-5'];
    }

    public function testSendsTheSystemPromptWithCacheControl(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt());

        $request = $this->request($response);

        $this->assertSame([['type' => 'text', 'text' => 'System', 'cache_control' => ['type' => 'ephemeral']]], $request['system']);
        $this->assertSame(16000, $request['max_tokens']);
        $this->assertArrayNotHasKey('temperature', $request);
        $this->assertArrayNotHasKey('thinking', $request);
    }

    public function testSendsTheAttachmentsBeforeTheText(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt([
            new AiAttachment('factura.pdf', 'application/pdf', '%PDF-1.4'),
            new AiAttachment('ticket.png', 'image/png', 'PNG'),
        ]));

        $this->assertSame([[
            'role' => 'user',
            'content' => [
                ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode('%PDF-1.4')]],
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => base64_encode('PNG')]],
                ['type' => 'text', 'text' => 'Prompt'],
            ],
        ]], $this->request($response)['messages']);
    }

    public function testSendsTheEffortAndTheNormalizedJsonSchema(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt(effort: AiEffort::HIGH));

        $this->assertSame([
            'effort' => 'high',
            'format' => [
                'type' => 'json_schema',
                'schema' => [
                    'type' => 'object',
                    'properties' => ['content' => ['type' => 'string']],
                    'required' => ['content'],
                    'additionalProperties' => false,
                ],
            ],
        ], $this->request($response)['output_config']);
    }

    public function testSendsTheRequiredHeadersAndTimeout(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt());

        $options = $response->getRequestOptions();
        $headers = $options['normalized_headers'];

        $this->assertSame(['x-api-key: key'], $headers['x-api-key']);
        $this->assertSame(['anthropic-version: 2026-10-21'], $headers['anthropic-version']);
        $this->assertSame(300.0, $options['timeout']);
        $this->assertSame(300.0, $options['max_duration']);
    }

    public function testReturnsTheParsedJsonAndTheUsage(): void
    {
        $response = $this->response([
            'model' => 'claude-sonnet-5-5',
            'content' => [
                ['type' => 'thinking', 'thinking' => 'Razonamiento'],
                ['type' => 'text', 'text' => '{"content": "Texto"}'],
            ],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 3000, 'output_tokens' => 800, 'cache_read_input_tokens' => 0, 'cache_creation_input_tokens' => 0],
        ]);

        $result = $this->client($response)->send(new FakeAiPrompt());

        $this->assertSame(['content' => 'Texto'], $result->data);
        $this->assertSame('end_turn', $result->stopReason);
        $this->assertSame(AiProvider::Anthropic, $result->usage->provider);
        $this->assertSame('claude-sonnet-5-5', $result->usage->model);
        $this->assertSame(3000, $result->usage->inputTokens);
        $this->assertSame(800, $result->usage->outputTokens);
        $this->assertSame(0.014, $result->usage->costUsd);
        $this->assertGreaterThanOrEqual(0, $result->usage->durationMs);
    }

    public function testFailsWhenTheResponseIsCutOff(): void
    {
        $response = $this->response([
            'content' => [['type' => 'text', 'text' => '{"content": "Tex']],
            'stop_reason' => 'max_tokens',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 16000],
        ]);

        try {
            $this->client($response)->send(new FakeAiPrompt());
            $this->fail('An AiResponseException was expected');
        } catch (AiResponseException $exception) {
            $this->assertStringContainsString('did not finish (max_tokens) after 16000 output tokens', $exception->getMessage());
            $this->assertSame(16000, $exception->usage()?->outputTokens);
        }
    }

    public function testFailsWhenTheRequestIsRefused(): void
    {
        $response = $this->response([
            'content' => [],
            'stop_reason' => 'refusal',
            'stop_details' => ['type' => 'refusal', 'category' => 'cyber'],
        ]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (refusal, category cyber)');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWithTheApiErrorMessage(): void
    {
        $response = new MockResponse((string) json_encode([
            'type' => 'error',
            'error' => ['type' => 'rate_limit_error', 'message' => 'Number of request tokens has exceeded your rate limit'],
        ]), ['http_code' => 429]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to claude-sonnet-5-5 failed with HTTP 429: Number of request tokens has exceeded your rate limit');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWhenTheTextIsNotJson(): void
    {
        $response = $this->response(['content' => [['type' => 'text', 'text' => 'No es JSON']], 'stop_reason' => 'end_turn']);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWithoutApiKeyAndWithoutRequest(): void
    {
        $response = $this->successfulResponse();
        $httpClient = new MockHttpClient($response);
        $client = new AnthropicAiClient($httpClient, new JsonSchemaNormalizer(), new AnthropicUsageReader(new AnthropicPricing()), new AiRequestLog(false, ''), new FixedClock(), '', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5');

        try {
            $client->send(new FakeAiPrompt());
            $this->fail('An AiResponseException was expected');
        } catch (AiResponseException $exception) {
            $this->assertSame('The AI provider "anthropic" is not configured', $exception->getMessage());
        }

        $this->assertSame(0, $httpClient->getRequestsCount());
    }

    public function testFailsWithAnUnsupportedAttachment(): void
    {
        $httpClient = new MockHttpClient($this->successfulResponse());
        $client = new AnthropicAiClient($httpClient, new JsonSchemaNormalizer(), new AnthropicUsageReader(new AnthropicPricing()), new AiRequestLog(false, ''), new FixedClock(), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5');

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('does not support attachments of type "text/plain"');

        $client->send(new FakeAiPrompt([new AiAttachment('notas.txt', 'text/plain', 'Hola')]));
    }

    public function testWritesTheLogWithoutTheAttachmentData(): void
    {
        $response = $this->successfulResponse();
        $client = new AnthropicAiClient(new MockHttpClient($response), new JsonSchemaNormalizer(), new AnthropicUsageReader(new AnthropicPricing()), new AiRequestLog(true, $this->logDir), new FixedClock(), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5');

        $client->send(new FakeAiPrompt([new AiAttachment('factura.pdf', 'application/pdf', '%PDF-1.4')]));

        $files = glob($this->logDir . '/*_fake-prompt.json') ?: [];
        $this->assertCount(1, $files);

        $contents = (string) file_get_contents($files[0]);
        $this->assertStringNotContainsString(base64_encode('%PDF-1.4'), $contents);

        $log = json_decode($contents, true);
        $this->assertIsArray($log);
        $this->assertSame('factura.pdf (8 bytes)', $log['request']['body']['messages'][0]['content'][0]['source']['data']);
        $this->assertSame(['content' => 'Texto'], $log['parsed_result']);
    }

    private function successfulResponse(): MockResponse
    {
        return $this->response(['content' => [['type' => 'text', 'text' => '{"content": "Texto"}']], 'stop_reason' => 'end_turn']);
    }

    /** @param array<mixed> $body */
    private function response(array $body): MockResponse
    {
        return new MockResponse((string) json_encode($body));
    }

    /** @return array<mixed> */
    private function request(MockResponse $response): array
    {
        $request = json_decode((string) $response->getRequestOptions()['body'], true);

        $this->assertIsArray($request);

        return $request;
    }

    private function client(MockResponse $response): AnthropicAiClient
    {
        return new AnthropicAiClient(new MockHttpClient($response), new JsonSchemaNormalizer(), new AnthropicUsageReader(new AnthropicPricing()), new AiRequestLog(false, ''), new FixedClock(), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5');
    }
}
