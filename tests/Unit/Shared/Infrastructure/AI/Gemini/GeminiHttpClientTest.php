<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\AI\Gemini;

use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiPrompt;
use App\Shared\Infrastructure\AI\AiRequestLog;
use App\Shared\Infrastructure\AI\AiResponseException;
use App\Shared\Infrastructure\AI\AiThinkingLevel;
use App\Shared\Infrastructure\AI\Gemini\GeminiHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeminiHttpClientTest extends TestCase
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
     * @dataProvider thinkingConfigs
     *
     * @param array<string, int|string>|null $expectedThinkingConfig
     */
    public function testSendsTheThinkingConfigOfTheModel(string $model, AiThinkingLevel $level, ?array $expectedThinkingConfig): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": "Texto"}']]]]]]);

        $this->client($response, $model)->send($this->prompt($level));

        $request = json_decode((string) $response->getRequestOptions()['body'], true);

        $this->assertIsArray($request);
        $this->assertSame($expectedThinkingConfig, $request['generationConfig']['thinkingConfig'] ?? null);
        $this->assertStringContainsString(sprintf('/models/%s:generateContent', $model), $response->getRequestUrl());
    }

    /**
     * @return iterable<string, array{string, AiThinkingLevel, array<string, int|string>|null}>
     */
    public static function thinkingConfigs(): iterable
    {
        yield 'gemini 3 flash minimal' => ['gemini-3-flash-preview', AiThinkingLevel::MINIMAL, ['thinkingLevel' => 'minimal']];
        yield 'gemini 3 flash medium' => ['gemini-3-flash-preview', AiThinkingLevel::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 3 pro minimal' => ['gemini-3-pro-preview', AiThinkingLevel::MINIMAL, ['thinkingLevel' => 'low']];
        yield 'gemini 3 pro medium' => ['gemini-3-pro-preview', AiThinkingLevel::MEDIUM, ['thinkingLevel' => 'high']];
        yield 'gemini 3.1 pro medium' => ['gemini-3.1-pro-preview', AiThinkingLevel::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 3.1 pro minimal' => ['gemini-3.1-pro-preview', AiThinkingLevel::MINIMAL, ['thinkingLevel' => 'low']];
        yield 'gemini 3.5 flash lite minimal' => ['gemini-3.5-flash-lite', AiThinkingLevel::MINIMAL, ['thinkingLevel' => 'minimal']];
        yield 'gemini 3.8 flash minimal' => ['gemini-3.8-flash', AiThinkingLevel::MINIMAL, ['thinkingLevel' => 'low']];
        yield 'gemini 3.8 flash medium' => ['gemini-3.8-flash', AiThinkingLevel::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 4 argon minimal' => ['gemini-4-argon', AiThinkingLevel::MINIMAL, ['thinkingLevel' => 'low']];
        yield 'gemini 2.5 flash high' => ['gemini-2.5-flash', AiThinkingLevel::HIGH, ['thinkingBudget' => 24576]];
        yield 'gemini 2.5 flash minimal' => ['gemini-2.5-flash', AiThinkingLevel::MINIMAL, ['thinkingBudget' => 0]];
        yield 'gemini 2.5 flash low' => ['gemini-2.5-flash', AiThinkingLevel::LOW, ['thinkingBudget' => 1024]];
        yield 'gemini 2.5 pro minimal' => ['gemini-2.5-pro', AiThinkingLevel::MINIMAL, ['thinkingBudget' => 128]];
        yield 'gemini 2.0 flash' => ['gemini-2.0-flash', AiThinkingLevel::LOW, null];
    }

    public function testDoesNotSendSamplingParameters(): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": "Texto"}']]]]]]);

        $this->client($response, 'gemini-3-flash-preview')->send($this->prompt());

        $request = json_decode((string) $response->getRequestOptions()['body'], true);

        $this->assertIsArray($request);
        $this->assertArrayNotHasKey('temperature', $request['generationConfig']);
        $this->assertArrayNotHasKey('topP', $request['generationConfig']);
        $this->assertArrayNotHasKey('topK', $request['generationConfig']);
    }

    /**
     * @dataProvider timeouts
     */
    public function testSendsTheTimeoutOfTheThinkingLevel(AiThinkingLevel $level, float $expectedTimeout): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": "Texto"}']]]]]]);

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

    public function testReturnsTheParsedJson(): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '[{"id": "a", "text": "A"}]']]]]]]);

        $this->assertSame([['id' => 'a', 'text' => 'A']], $this->client($response)->send($this->prompt()));
    }

    public function testFailsWhenTheResponseIsCutOff(): void
    {
        $response = $this->response([
            'candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => '[{"id": "a", "text": "A']]]]],
            'usageMetadata' => ['candidatesTokenCount' => 2609, 'thoughtsTokenCount' => 62912],
        ]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (MAX_TOKENS) after 2609 output tokens and 62912 thinking tokens');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenThePromptIsBlocked(): void
    {
        $response = $this->response(['promptFeedback' => ['blockReason' => 'SAFETY']]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (SAFETY)');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenTheJsonIsInvalid(): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": ']]]]]]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWithTheApiErrorMessage(): void
    {
        $response = new MockResponse((string) json_encode([
            'error' => ['code' => 429, 'message' => 'Resource has been exhausted', 'status' => 'RESOURCE_EXHAUSTED'],
        ]), ['http_code' => 429]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to gemini-3-flash-preview failed with HTTP 429: Resource has been exhausted');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWithTheStatusCodeWhenTheErrorIsNotJson(): void
    {
        $response = new MockResponse('<html>Bad gateway</html>', ['http_code' => 502]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to gemini-3-flash-preview failed with HTTP 502: <html>Bad gateway</html>');

        $this->client($response)->send($this->prompt());
    }

    public function testWritesTheLogOfTheCall(): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": "Texto"}']]]]]]);
        $client = new GeminiHttpClient(new MockHttpClient($response), 'key', 'gemini-2.5-flash', 'gemini-3-flash-preview', 'gemini-3-flash-preview', new AiRequestLog(true, $this->logDir));

        $client->send(new LoggedGeminiPrompt());

        $files = glob($this->logDir . '/*_LoggedGeminiPrompt.json') ?: [];
        $this->assertCount(1, $files);

        $log = json_decode((string) file_get_contents($files[0]), true);
        $this->assertIsArray($log);
        $this->assertSame('gemini', $log['provider']);
        $this->assertSame('gemini-3-flash-preview', $log['model']);
        $this->assertSame(json_decode((string) $response->getRequestOptions()['body'], true), $log['request']['body']);
        $this->assertSame('STOP', $log['raw_response']['candidates'][0]['finishReason']);
        $this->assertSame(['content' => 'Texto'], $log['parsed_result']);
    }

    /**
     * @param array<mixed> $body
     */
    private function response(array $body): MockResponse
    {
        return new MockResponse((string) json_encode($body));
    }

    private function client(MockResponse $response, string $model = 'gemini-3-flash-preview'): GeminiHttpClient
    {
        return new GeminiHttpClient(new MockHttpClient($response), 'key', 'gemini-2.5-flash', $model, $model, new AiRequestLog(false, ''));
    }

    private function prompt(AiThinkingLevel $level = AiThinkingLevel::LOW): AiPrompt
    {
        return new class ($level) implements AiPrompt {
            public function __construct(private readonly AiThinkingLevel $level) {}

            public function text(): string
            {
                return 'Prompt';
            }

            public function responseSchema(): array
            {
                return ['type' => 'OBJECT'];
            }

            public function modelTier(): AiModelTier
            {
                return AiModelTier::MEDIUM;
            }

            public function thinkingLevel(): AiThinkingLevel
            {
                return $this->level;
            }
        };
    }
}

class LoggedGeminiPrompt implements AiPrompt
{
    public function text(): string
    {
        return 'Prompt';
    }

    public function responseSchema(): array
    {
        return ['type' => 'OBJECT'];
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
