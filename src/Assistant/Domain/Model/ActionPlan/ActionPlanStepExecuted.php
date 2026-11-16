<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanStepExecuted extends DomainEvent
{
    protected static string $eventName = 'actionPlanStepExecuted';

    /**
     * @param array<string, string> $outputs
     * @param list<string>          $linkParameters
     */
    public function __construct(
        private readonly ActionPlanId $planId,
        private readonly int $position,
        private readonly string $tool,
        private readonly string $summary,
        private readonly array $outputs,
        private readonly ?string $linkLabel,
        private readonly ?string $linkRoute,
        private readonly array $linkParameters,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function tool(): string
    {
        return $this->tool;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    /** @return array<string, string> */
    public function outputs(): array
    {
        return $this->outputs;
    }

    public function linkLabel(): ?string
    {
        return $this->linkLabel;
    }

    public function linkRoute(): ?string
    {
        return $this->linkRoute;
    }

    /** @return list<string> */
    public function linkParameters(): array
    {
        return $this->linkParameters;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
            'position' => $this->position,
            'tool' => $this->tool,
            'summary' => $this->summary,
            'outputs' => $this->outputs,
            'linkLabel' => $this->linkLabel,
            'linkRoute' => $this->linkRoute,
            'linkParameters' => $this->linkParameters,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var array<string, string> $outputs */
        $outputs = is_array($payload['outputs'] ?? null) ? $payload['outputs'] : [];
        /** @var list<string> $linkParameters */
        $linkParameters = is_array($payload['linkParameters'] ?? null) ? $payload['linkParameters'] : [];

        return new ActionPlanStepExecuted(
            planId: new ActionPlanId((string) $payload['planId']),
            position: (int) $payload['position'],
            tool: (string) $payload['tool'],
            summary: (string) $payload['summary'],
            outputs: $outputs,
            linkLabel: isset($payload['linkLabel']) ? (string) $payload['linkLabel'] : null,
            linkRoute: isset($payload['linkRoute']) ? (string) $payload['linkRoute'] : null,
            linkParameters: $linkParameters,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
