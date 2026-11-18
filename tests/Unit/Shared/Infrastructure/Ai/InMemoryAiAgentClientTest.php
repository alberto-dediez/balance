<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Infrastructure\Ai\InMemoryAiAgentClient;
use PHPUnit\Framework\TestCase;

class InMemoryAiAgentClientTest extends TestCase
{
    public function testReturnsTheQueuedStepsInOrder(): void
    {
        $client = new InMemoryAiAgentClient();
        $client->enqueueToolCalls(new AiToolCall('t1', 'list_clients', ['name' => 'Acme']));
        $client->enqueueText('Hecho');

        $first = $client->step($this->request());
        $second = $client->step($this->request());

        $this->assertSame(AiStopReason::ToolUse, $first->stopReason);
        $this->assertSame('list_clients', $first->toolCalls()[0]->name);
        $this->assertSame(AiStopReason::EndTurn, $second->stopReason);
        $this->assertSame('Hecho', $second->text());
        $this->assertCount(2, $client->requests());
    }

    public function testBuildsAStepFromTheRequest(): void
    {
        $client = new InMemoryAiAgentClient();
        $client->enqueueUsing(static fn(AiAgentRequest $request): AiAgentStep => new AiAgentStep(AiAgentMessage::assistantText($request->name), AiUsage::none(), AiStopReason::EndTurn));

        $this->assertSame('test', $client->step($this->request())->text());
    }

    public function testWithoutQueueReturnsAnEmptyEndTurn(): void
    {
        $step = (new InMemoryAiAgentClient())->step($this->request());

        $this->assertSame(AiStopReason::EndTurn, $step->stopReason);
        $this->assertSame('', $step->text());
    }

    public function testFailsWhenAskedTo(): void
    {
        $client = new InMemoryAiAgentClient();
        $client->fail('Caído');

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('Caído');

        $client->step($this->request());
    }

    public function testResetClearsQueueAndRequests(): void
    {
        $client = new InMemoryAiAgentClient();
        $client->enqueueText('Hola');
        $client->step($this->request());
        $client->enqueueText('Otra');
        $client->reset();

        $this->assertSame([], $client->requests());
        $this->assertNull($client->lastRequest());
        $this->assertSame('', $client->step($this->request())->text());
    }

    private function request(): AiAgentRequest
    {
        return new AiAgentRequest('test', 'Sistema', [], [AiAgentMessage::userText('Hola')], AiModelTier::LOW, AiEffort::LOW);
    }
}
