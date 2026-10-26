<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\AI\Anthropic;

use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiPrompt;
use App\Shared\Infrastructure\AI\AiRequestLog;
use App\Shared\Infrastructure\AI\AiResponseException;
use App\Shared\Infrastructure\AI\AiThinkingLevel;
use App\Shared\Infrastructure\AI\Anthropic\AnthropicHttpClient;
use App\Shared\Infrastructure\AI\Anthropic\JsonSchemaNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AnthropicHttpClientTest extends TestCase
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
     * @dataProvider efforts
     */
    public function testSendsTheEffortOfTheThinkingLevel(AiThinkingLevel $level, string $expectedEffort): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send($this->prompt($level));

        $this->assertSame($expectedEffort, $this->request($response)['output_config']['effort']);
    }

    /**
     * @return iterable<string, array{AiThinkingLevel, string}>
     */
    public static function efforts(): iterable
    {
        yield 'minimal' => [AiThinkingLevel::MINIMAL, 'low'];
        yield 'low' => [AiThinkingLevel::LOW, 'low'];
        yield 'medium' => [AiThinkingLevel::MEDIUM, 'medium'];
        yield 'high' => [AiThinkingLevel::HIGH, 'high'];
    }

    /**
     * @dataProvider models
     */
    public function testSendsTheModelOfTheTier(AiModelTier $tier, string $expectedModel): void
    {
        $response = $this->successfulResponse();

        $this->client($response, 'claude-sonnet-5-5')->send($this->prompt(AiThinkingLevel::LOW, $tier));

        $this->assertSame($expectedModel, $this->request($response)['model']);
        $this->assertSame('https://api.anthropic.com/v1/messages', $response->getRequestUrl());
    }

    /**
     * @return iterable<string, array{AiModelTier, string}>
     */
    public static function models(): iterable
    {
        yield 'low' => [AiModelTier::LOW, 'claude-haiku-5-5'];
        yield 'medium' => [AiModelTier::MEDIUM, 'claude-sonnet-5-5'];
        yield 'high' => [AiModelTier::HIGH, 'claude-sonnet-5-5'];
    }

    public function testSendsTheRequiredHeaders(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send($this->prompt());

        $headers = $response->getRequestOptions()['normalized_headers'];

        $this->assertSame(['x-api-key: key'], $headers['x-api-key']);
        $this->assertSame(['anthropic-version: 2026-10-21'], $headers['anthropic-version']);
        $this->assertArrayNotHasKey('anthropic-beta', $headers);
    }

    /**
     * @dataProvider timeouts
     */
    public function testSendsTheTimeoutOfTheThinkingLevel(AiThinkingLevel $level, float $expectedTimeout): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send($this->prompt($level));

        $this->assertSame($expectedTimeout, $response->getRequestOptions()['timeout']);
    }

    /**
     * @return iterable<string, array{AiThinkingLevel, float}>
     */
    public static function timeouts(): iterable
    {
        yield 'medium' => [AiThinkingLevel::MEDIUM, 300.0];
        yield 'high' => [AiThinkingLevel::HIGH, 600.0];
    }

    public function testSendsTheNormalizedJsonSchema(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send($this->prompt());

        $this->assertSame([
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => ['content' => ['type' => 'string']],
                'required' => ['content'],
                'additionalProperties' => false,
            ],
        ], $this->request($response)['output_config']['format']);
    }

    public function testDoesNotSendThinkingNorSamplingParameters(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send($this->prompt());

        $request = $this->request($response);

        $this->assertArrayNotHasKey('thinking', $request);
        $this->assertArrayNotHasKey('temperature', $request);
        $this->assertArrayNotHasKey('top_p', $request);
        $this->assertArrayNotHasKey('top_k', $request);
        $this->assertArrayNotHasKey('fallbacks', $request);
        $this->assertSame(32000, $request['max_tokens']);
        $this->assertSame([['role' => 'user', 'content' => 'Prompt']], $request['messages']);
    }

    public function testReturnsTheParsedJsonOfTheFirstTextBlock(): void
    {
        $response = $this->response([
            'content' => [
                ['type' => 'thinking', 'thinking' => 'Razonamiento'],
                ['type' => 'text', 'text' => '[{"id": "a", "text": "A"}]'],
            ],
            'stop_reason' => 'end_turn',
        ]);

        $this->assertSame([['id' => 'a', 'text' => 'A']], $this->client($response)->send($this->prompt()));
    }

    public function testFailsWhenTheResponseIsCutOff(): void
    {
        $response = $this->response([
            'content' => [['type' => 'text', 'text' => '[{"id": "a", "text": "A']],
            'stop_reason' => 'max_tokens',
            'usage' => ['output_tokens' => 32000],
        ]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (max_tokens) after 32000 output tokens');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenTheRequestIsRefused(): void
    {
        $response = $this->response([
            'content' => [],
            'stop_reason' => 'refusal',
            'stop_details' => ['type' => 'refusal', 'category' => 'cyber'],
        ]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (refusal) after 0 output tokens (category cyber)');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenTheJsonIsInvalid(): void
    {
        $response = $this->response(['content' => [['type' => 'text', 'text' => '{"content": ']], 'stop_reason' => 'end_turn']);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenThereIsNoTextBlock(): void
    {
        $response = $this->response(['content' => [['type' => 'thinking', 'thinking' => 'Razonamiento']], 'stop_reason' => 'end_turn']);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWithTheApiErrorMessage(): void
    {
        $response = new MockResponse((string) json_encode([
            'type' => 'error',
            'error' => ['type' => 'invalid_request_error', 'message' => 'output_config.format.schema: unsupported keyword'],
        ]), ['http_code' => 400]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to claude-sonnet-5-5 failed with HTTP 400: output_config.format.schema: unsupported keyword');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWithTheStatusCodeWhenTheErrorIsNotJson(): void
    {
        $response = new MockResponse('<html>Bad gateway</html>', ['http_code' => 502]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to claude-sonnet-5-5 failed with HTTP 502: <html>Bad gateway</html>');

        $this->client($response)->send($this->prompt());
    }

    public function testWritesTheLogOfTheCall(): void
    {
        $response = $this->successfulResponse();
        $client = new AnthropicHttpClient(new MockHttpClient($response), new JsonSchemaNormalizer(), new AiRequestLog(true, $this->logDir), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-sonnet-5-5');

        $client->send(new LoggedAnthropicPrompt());

        $files = glob($this->logDir . '/*_LoggedAnthropicPrompt.json') ?: [];
        $this->assertCount(1, $files);

        $log = json_decode((string) file_get_contents($files[0]), true);
        $this->assertIsArray($log);
        $this->assertSame('anthropic', $log['provider']);
        $this->assertSame('claude-sonnet-5-5', $log['model']);
        $this->assertSame($this->request($response), $log['request']['body']);
        $this->assertSame('end_turn', $log['raw_response']['stop_reason']);
        $this->assertSame(['content' => 'Texto'], $log['parsed_result']);
    }

    public function testWritesTheApiErrorInTheLog(): void
    {
        $response = new MockResponse((string) json_encode([
            'type' => 'error',
            'error' => ['type' => 'overloaded_error', 'message' => 'Overloaded'],
        ]), ['http_code' => 529]);
        $client = new AnthropicHttpClient(new MockHttpClient($response), new JsonSchemaNormalizer(), new AiRequestLog(true, $this->logDir), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-sonnet-5-5');

        try {
            $client->send(new LoggedAnthropicPrompt());
            $this->fail('An AiResponseException was expected');
        } catch (AiResponseException $exception) {
            $this->assertStringContainsString('failed with HTTP 529: Overloaded', $exception->getMessage());
        }

        $files = glob($this->logDir . '/*_LoggedAnthropicPrompt.json') ?: [];
        $this->assertCount(1, $files);

        $log = json_decode((string) file_get_contents($files[0]), true);
        $this->assertIsArray($log);
        $this->assertSame('Overloaded', $log['raw_response']['error']['message']);
        $this->assertSame([], $log['parsed_result']);
    }

    private function successfulResponse(): MockResponse
    {
        return $this->response(['content' => [['type' => 'text', 'text' => '{"content": "Texto"}']], 'stop_reason' => 'end_turn']);
    }

    /**
     * @param array<mixed> $body
     */
    private function response(array $body): MockResponse
    {
        return new MockResponse((string) json_encode($body));
    }

    /**
     * @return array<mixed>
     */
    private function request(MockResponse $response): array
    {
        $request = json_decode((string) $response->getRequestOptions()['body'], true);

        $this->assertIsArray($request);

        return $request;
    }

    private function client(MockResponse $response, string $model = 'claude-sonnet-5-5'): AnthropicHttpClient
    {
        return new AnthropicHttpClient(new MockHttpClient($response), new JsonSchemaNormalizer(), new AiRequestLog(false, ''), 'key', 'claude-haiku-5-5', $model, $model);
    }

    private function prompt(AiThinkingLevel $level = AiThinkingLevel::LOW, AiModelTier $tier = AiModelTier::MEDIUM): AiPrompt
    {
        return new class ($level, $tier) implements AiPrompt {
            public function __construct(
                private readonly AiThinkingLevel $level,
                private readonly AiModelTier $tier,
            ) {}

            public function text(): string
            {
                return 'Prompt';
            }

            public function responseSchema(): array
            {
                return ['type' => 'OBJECT', 'properties' => ['content' => ['type' => 'STRING']], 'required' => ['content']];
            }

            public function modelTier(): AiModelTier
            {
                return $this->tier;
            }

            public function thinkingLevel(): AiThinkingLevel
            {
                return $this->level;
            }
        };
    }
}

class LoggedAnthropicPrompt implements AiPrompt
{
    public function text(): string
    {
        return 'Prompt';
    }

    public function responseSchema(): array
    {
        return ['type' => 'OBJECT', 'properties' => ['content' => ['type' => 'STRING']], 'required' => ['content']];
    }

    public function modelTier(): AiModelTier
    {
        return AiModelTier::MEDIUM;
    }

    public function thinkingLevel(): AiThinkingLevel
    {
        return AiThinkingLevel::LOW;
    }
}
