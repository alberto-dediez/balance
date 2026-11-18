<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentClient;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAiAgentClient implements AiAgentClient
{
    /** @var list<AiAgentStep|AiResponseException|\Closure(AiAgentRequest): AiAgentStep> */
    private array $queue = [];

    /** @var list<AiAgentRequest> */
    private array $requests = [];

    public function enqueueText(string $text, ?AiUsage $usage = null): void
    {
        $this->queue[] = new AiAgentStep(
            new AiAgentMessage(AiAgentRole::Assistant, [AiAgentBlock::text($text)]),
            $usage ?? InMemoryAiClient::defaultUsage(),
            AiStopReason::EndTurn,
        );
    }

    public function enqueueToolCalls(AiToolCall ...$calls): void
    {
        $this->queue[] = new AiAgentStep(
            new AiAgentMessage(
                AiAgentRole::Assistant,
                array_values(array_map(static fn(AiToolCall $call): AiAgentBlock => AiAgentBlock::toolUse($call->id, $call->name, $call->input), $calls)),
            ),
            InMemoryAiClient::defaultUsage(),
            AiStopReason::ToolUse,
        );
    }

    public function enqueueStep(AiAgentStep $step): void
    {
        $this->queue[] = $step;
    }

    /** @param \Closure(AiAgentRequest): AiAgentStep $factory */
    public function enqueueUsing(\Closure $factory): void
    {
        $this->queue[] = $factory;
    }

    public function fail(string $message): void
    {
        $this->queue[] = new AiResponseException($message);
    }

    public function step(AiAgentRequest $request): AiAgentStep
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue);

        if ($next instanceof AiResponseException) {
            throw $next;
        }

        if ($next instanceof \Closure) {
            return $next($request);
        }

        return $next ?? new AiAgentStep(new AiAgentMessage(AiAgentRole::Assistant, [AiAgentBlock::text('')]), AiUsage::none(), AiStopReason::EndTurn);
    }

    /** @return list<AiAgentRequest> */
    public function requests(): array
    {
        return $this->requests;
    }

    public function lastRequest(): ?AiAgentRequest
    {
        return [] === $this->requests ? null : $this->requests[array_key_last($this->requests)];
    }

    public function pending(): int
    {
        return count($this->queue);
    }

    public function reset(): void
    {
        $this->queue = [];
        $this->requests = [];
    }
}
