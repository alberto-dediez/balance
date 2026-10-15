<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\AI\Gemini;

use App\Shared\Infrastructure\AI\Gemini\GeminiHttpClient;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;
use App\Shared\Infrastructure\AI\Gemini\GeminiResponseException;
use App\Shared\Infrastructure\AI\Gemini\GeminiThinkingLevel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeminiHttpClientTest extends TestCase
{
    /**
     * @dataProvider thinkingConfigs
     *
     * @param array<string, int|string>|null $expectedThinkingConfig
     */
    public function testSendsTheThinkingConfigOfTheModel(string $model, GeminiThinkingLevel $level, ?array $expectedThinkingConfig): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": "Texto"}']]]]]]);

        $this->client($response, $model)->send($this->prompt($level));

        $request = json_decode((string) $response->getRequestOptions()['body'], true);

        $this->assertIsArray($request);
        $this->assertSame($expectedThinkingConfig, $request['generationConfig']['thinkingConfig'] ?? null);
        $this->assertStringContainsString(sprintf('/models/%s:generateContent', $model), $response->getRequestUrl());
    }

    /**
     * @return iterable<string, array{string, GeminiThinkingLevel, array<string, int|string>|null}>
     */
    public static function thinkingConfigs(): iterable
    {
        yield 'gemini 3 flash minimal' => ['gemini-3-flash-preview', GeminiThinkingLevel::MINIMAL, ['thinkingLevel' => 'minimal']];
        yield 'gemini 3 flash medium' => ['gemini-3-flash-preview', GeminiThinkingLevel::MEDIUM, ['thinkingLevel' => 'medium']];
        yield 'gemini 3 pro minimal' => ['gemini-3-pro-preview', GeminiThinkingLevel::MINIMAL, ['thinkingLevel' => 'low']];
        yield 'gemini 3 pro medium' => ['gemini-3-pro-preview', GeminiThinkingLevel::MEDIUM, ['thinkingLevel' => 'high']];
        yield 'gemini 2.5 flash minimal' => ['gemini-2.5-flash', GeminiThinkingLevel::MINIMAL, ['thinkingBudget' => 0]];
        yield 'gemini 2.5 flash low' => ['gemini-2.5-flash', GeminiThinkingLevel::LOW, ['thinkingBudget' => 1024]];
        yield 'gemini 2.5 pro minimal' => ['gemini-2.5-pro', GeminiThinkingLevel::MINIMAL, ['thinkingBudget' => 128]];
        yield 'gemini 2.0 flash' => ['gemini-2.0-flash', GeminiThinkingLevel::LOW, null];
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

        $this->expectException(GeminiResponseException::class);
        $this->expectExceptionMessage('did not finish (MAX_TOKENS) after 2609 output tokens and 62912 thinking tokens');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenThePromptIsBlocked(): void
    {
        $response = $this->response(['promptFeedback' => ['blockReason' => 'SAFETY']]);

        $this->expectException(GeminiResponseException::class);
        $this->expectExceptionMessage('did not finish (SAFETY)');

        $this->client($response)->send($this->prompt());
    }

    public function testFailsWhenTheJsonIsInvalid(): void
    {
        $response = $this->response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"content": ']]]]]]);

        $this->expectException(GeminiResponseException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->client($response)->send($this->prompt());
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
        return new GeminiHttpClient(new MockHttpClient($response), 'key', 'gemini-2.5-flash', $model, $model);
    }

    private function prompt(GeminiThinkingLevel $level = GeminiThinkingLevel::LOW): GeminiPrompt
    {
        return new class ($level) implements GeminiPrompt {
            public function __construct(private readonly GeminiThinkingLevel $level) {}

            public function text(): string
            {
                return 'Prompt';
            }

            public function responseSchema(): array
            {
                return ['type' => 'OBJECT'];
            }

            public function temperature(): float
            {
                return 0.2;
            }

            public function modelTier(): GeminiModelTier
            {
                return GeminiModelTier::MEDIUM;
            }

            public function thinkingLevel(): GeminiThinkingLevel
            {
                return $this->level;
            }
        };
    }
}
