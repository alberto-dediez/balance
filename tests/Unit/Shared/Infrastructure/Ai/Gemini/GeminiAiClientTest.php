<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai\Gemini;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use App\Shared\Infrastructure\Ai\Gemini\GeminiAiClient;
use App\Tests\Unit\Shared\Application\Clock\FixedClock;
use App\Tests\Unit\Shared\Infrastructure\Ai\FakeAiPrompt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeminiAiClientTest extends TestCase
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
    public function testSendsTheThinkingConfigOfTheModel(string $model, AiEffort $effort, ?array $expectedThinkingConfig): void
    {
        $response = $this->successfulResponse();

        $this->client($response, $model)->send(new FakeAiPrompt(effort: $effort));

        $this->assertSame($expectedThinkingConfig, $this->request($response)['generationConfig']['thinkingConfig'] ?? null);
        $this->assertStringContainsString(sprintf('/models/%s:generateContent', $model), $response->getRequestUrl());
    }

    /** @return iterable<string, array{string, AiEffort, array<string, int|string>|null}> */
    public static function thinkingConfigs(): iterable
    {
        yield 'gemini 3 flash medium' => ['gemini-3-flash-preview', AiEffort::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 3 pro low' => ['gemini-3-pro-preview', AiEffort::LOW, ['thinkingLevel' => 'low']];
        yield 'gemini 3 pro medium' => ['gemini-3-pro-preview', AiEffort::MEDIUM, ['thinkingLevel' => 'high']];
        yield 'gemini 3.1 pro medium' => ['gemini-3.1-pro-preview', AiEffort::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 3.8 flash medium' => ['gemini-3.8-flash', AiEffort::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 4 argon high' => ['gemini-4-argon', AiEffort::HIGH, ['thinkingLevel' => 'high']];
        yield 'gemini 2.5 flash high' => ['gemini-2.5-flash', AiEffort::HIGH, ['thinkingBudget' => 24576]];
        yield 'gemini 2.5 flash low' => ['gemini-2.5-flash', AiEffort::LOW, ['thinkingBudget' => 1024]];
        yield 'gemini 2.0 flash' => ['gemini-2.0-flash', AiEffort::LOW, null];
    }

    public function testDoesNotSendSamplingParameters(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt());

        $request = $this->request($response);
        $this->assertArrayNotHasKey('temperature', $request['generationConfig']);
        $this->assertArrayNotHasKey('topP', $request['generationConfig']);
        $this->assertArrayNotHasKey('topK', $request['generationConfig']);
    }

    public function testSendsTheSystemInstructionAndTheAttachments(): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt([new AiAttachment('factura.pdf', 'application/pdf', '%PDF-1.4')]));

        $request = $this->request($response);
        $this->assertSame([['text' => 'System']], $request['systemInstruction']['parts']);
        $this->assertSame(['inlineData' => ['mimeType' => 'application/pdf', 'data' => base64_encode('%PDF-1.4')]], $request['contents'][0]['parts'][0]);
        $this->assertSame(['text' => 'Prompt'], $request['contents'][0]['parts'][1]);
    }

    public function testFailsWithAnUnsupportedAttachment(): void
    {
        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('does not support attachments of type "text/plain"');

        $this->client($this->successfulResponse())->send(new FakeAiPrompt([new AiAttachment('nota.txt', 'text/plain', 'Texto')]));
    }

    /**
     * @dataProvider timeouts
     */
    public function testSendsTheTimeoutOfTheEffort(AiEffort $effort, float $expectedTimeout): void
    {
        $response = $this->successfulResponse();

        $this->client($response)->send(new FakeAiPrompt(effort: $effort));

        $this->assertSame($expectedTimeout, $response->getRequestOptions()['timeout']);
    }

    /** @return iterable<string, array{AiEffort, float}> */
    public static function timeouts(): iterable
    {
        yield 'medium' => [AiEffort::MEDIUM, 300.0];
        yield 'high' => [AiEffort::HIGH, 600.0];
    }

    public function testReturnsTheParsedJsonAndTheUsage(): void
    {
        $response = $this->response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '[{"id": "a", "text": "A"}]']]]]],
            'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 20, 'thoughtsTokenCount' => 30, 'cachedContentTokenCount' => 10],
        ]);

        $result = $this->client($response)->send(new FakeAiPrompt());

        $this->assertSame([['id' => 'a', 'text' => 'A']], $result->data);
        $this->assertSame(AiProvider::Gemini, $result->usage->provider);
        $this->assertSame('gemini-3-flash-preview', $result->usage->model);
        $this->assertSame(100, $result->usage->inputTokens);
        $this->assertSame(50, $result->usage->outputTokens);
        $this->assertSame(10, $result->usage->cacheReadTokens);
    }

    public function testFailsWhenTheApiKeyIsMissing(): void
    {
        $client = new GeminiAiClient(new MockHttpClient($this->successfulResponse()), new AiRequestLog(false, ''), new FixedClock(), '', 'gemini-2.5-flash', 'gemini-3-flash-preview', 'gemini-3-flash-preview');

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI provider "gemini" is not configured');

        $client->send(new FakeAiPrompt());
    }

    public function testFailsWhenTheResponseIsCutOff(): void
    {
        $response = $this->response([
            'candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => '[{"id": "a", "text": "A']]]]],
            'usageMetadata' => ['candidatesTokenCount' => 2609, 'thoughtsTokenCount' => 62912],
        ]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (MAX_TOKENS) after 65521 output tokens');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWhenThePromptIsBlocked(): void
    {
        $response = $this->response(['promptFeedback' => ['blockReason' => 'SAFETY']]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('did not finish (SAFETY)');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWhenTheJsonIsInvalid(): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": ']]]]]]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWithTheApiErrorMessage(): void
    {
        $response = new MockResponse((string) json_encode([
            'error' => ['code' => 429, 'message' => 'Resource has been exhausted', 'status' => 'RESOURCE_EXHAUSTED'],
        ]), ['http_code' => 429]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to gemini-3-flash-preview failed with HTTP 429: Resource has been exhausted');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testFailsWithTheStatusCodeWhenTheErrorIsNotJson(): void
    {
        $response = new MockResponse('<html>Bad gateway</html>', ['http_code' => 502]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI request to gemini-3-flash-preview failed with HTTP 502: <html>Bad gateway</html>');

        $this->client($response)->send(new FakeAiPrompt());
    }

    public function testWritesTheLogOfTheCall(): void
    {
        $response = $this->successfulResponse();
        $client = new GeminiAiClient(new MockHttpClient($response), new AiRequestLog(true, $this->logDir), new FixedClock(), 'key', 'gemini-2.5-flash', 'gemini-3-flash-preview', 'gemini-3-flash-preview');

        $client->send(new FakeAiPrompt(name: 'gemini-prompt'));

        $files = glob($this->logDir . '/*_gemini-prompt.json') ?: [];
        $this->assertCount(1, $files);

        $log = json_decode((string) file_get_contents($files[0]), true);
        $this->assertIsArray($log);
        $this->assertSame('gemini', $log['provider']);
        $this->assertSame('gemini-3-flash-preview', $log['model']);
        $this->assertSame('STOP', $log['raw_response']['candidates'][0]['finishReason']);
        $this->assertSame(['content' => 'Texto'], $log['parsed_result']);
    }

    private function successfulResponse(): MockResponse
    {
        return $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": "Texto"}']]]]]]);
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

    private function client(MockResponse $response, string $model = 'gemini-3-flash-preview'): GeminiAiClient
    {
        return new GeminiAiClient(new MockHttpClient($response), new AiRequestLog(false, ''), new FixedClock(), 'key', 'gemini-2.5-flash', $model, $model);
    }
}
