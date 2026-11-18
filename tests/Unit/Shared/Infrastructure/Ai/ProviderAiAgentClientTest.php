<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Infrastructure\Ai\AiAgentProviderClient;
use App\Shared\Infrastructure\Ai\ProviderAiAgentClient;
use PHPUnit\Framework\TestCase;

class ProviderAiAgentClientTest extends TestCase
{
    public function testDelegatesInTheConfiguredProvider(): void
    {
        $this->assertSame('anthropic', $this->client('anthropic')->step($this->request())->text());
    }

    public function testFailsWhenTheProviderHasNoAdapter(): void
    {
        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('The AI provider "gemini" is not configured');

        $this->client('gemini')->step($this->request());
    }

    private function client(string $provider): ProviderAiAgentClient
    {
        return new ProviderAiAgentClient([new class implements AiAgentProviderClient {
            public function provider(): AiProvider
            {
                return AiProvider::Anthropic;
            }

            public function step(AiAgentRequest $request): AiAgentStep
            {
                return new AiAgentStep(new AiAgentMessage(AiAgentRole::Assistant, [AiAgentBlock::text('anthropic')]), AiUsage::none(), AiStopReason::EndTurn);
            }
        }], $provider);
    }

    private function request(): AiAgentRequest
    {
        return new AiAgentRequest(name: 'test', system: 'Sistema', tools: [], messages: [AiAgentMessage::userText('Hola')], modelTier: AiModelTier::LOW, effort: AiEffort::LOW);
    }
}
