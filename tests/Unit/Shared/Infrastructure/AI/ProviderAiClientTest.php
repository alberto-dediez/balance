<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\AI;

use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiPrompt;
use App\Shared\Infrastructure\AI\AiRequestLog;
use App\Shared\Infrastructure\AI\AiThinkingLevel;
use App\Shared\Infrastructure\AI\Anthropic\AnthropicHttpClient;
use App\Shared\Infrastructure\AI\Anthropic\JsonSchemaNormalizer;
use App\Shared\Infrastructure\AI\Gemini\GeminiHttpClient;
use App\Shared\Infrastructure\AI\ProviderAiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ProviderAiClientTest extends TestCase
{
    public function testDelegatesInGemini(): void
    {
        $this->assertSame(['provider' => 'gemini'], $this->client('gemini')->send($this->prompt()));
    }

    public function testDelegatesInAnthropic(): void
    {
        $this->assertSame(['provider' => 'anthropic'], $this->client('anthropic')->send($this->prompt()));
    }

    public function testFailsWithAnUnknownProvider(): void
    {
        $this->expectException(\ValueError::class);

        $this->client('openai');
    }

    private function client(string $provider): ProviderAiClient
    {
        $geminiResponse = new MockResponse((string) json_encode(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"provider": "gemini"}']]]]]]));
        $anthropicResponse = new MockResponse((string) json_encode(['content' => [['type' => 'text', 'text' => '{"provider": "anthropic"}']], 'stop_reason' => 'end_turn']));
        $log = new AiRequestLog(false, '');

        return new ProviderAiClient(
            new GeminiHttpClient(new MockHttpClient($geminiResponse), 'key', 'gemini-2.5-flash', 'gemini-2.5-flash', 'gemini-2.5-flash', $log),
            new AnthropicHttpClient(new MockHttpClient($anthropicResponse), new JsonSchemaNormalizer(), $log, 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-sonnet-5-5'),
            $provider,
        );
    }

    private function prompt(): AiPrompt
    {
        return new class implements AiPrompt {
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
                return AiModelTier::LOW;
            }

            public function thinkingLevel(): AiThinkingLevel
            {
                return AiThinkingLevel::LOW;
            }
        };
    }
}
