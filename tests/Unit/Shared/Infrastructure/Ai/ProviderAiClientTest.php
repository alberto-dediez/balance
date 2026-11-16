<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Infrastructure\Ai\AiRequestLog;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicAiClient;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicPricing;
use App\Shared\Infrastructure\Ai\Anthropic\AnthropicUsageReader;
use App\Shared\Infrastructure\Ai\Anthropic\JsonSchemaNormalizer;
use App\Shared\Infrastructure\Ai\ProviderAiClient;
use App\Tests\Unit\Shared\Application\Clock\FixedClock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ProviderAiClientTest extends TestCase
{
    public function testDelegatesInTheConfiguredProvider(): void
    {
        $this->assertSame(['provider' => 'anthropic'], $this->client('anthropic')->send(new FakeAiPrompt())->data);
    }

    public function testFailsWhenTheProviderHasNoAdapter(): void
    {
        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI provider "gemini" is not configured');

        $this->client('gemini')->send(new FakeAiPrompt());
    }

    private function client(string $provider): ProviderAiClient
    {
        $response = new MockResponse((string) json_encode(['content' => [['type' => 'text', 'text' => '{"provider": "anthropic"}']], 'stop_reason' => 'end_turn']));

        return new ProviderAiClient(
            [new AnthropicAiClient(new MockHttpClient($response), new JsonSchemaNormalizer(), new AnthropicUsageReader(new AnthropicPricing()), new AiRequestLog(false, ''), new FixedClock(), 'key', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5')],
            $provider,
        );
    }
}
