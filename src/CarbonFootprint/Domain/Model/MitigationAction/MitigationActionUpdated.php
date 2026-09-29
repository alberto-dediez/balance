<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationActionUpdated extends DomainEvent
{
    protected static string $eventName = 'mitigationActionUpdated';

    public function __construct(
        private readonly MitigationActionId $mitigationActionId,
        private readonly string $name,
        private readonly string $description,
        private readonly int $targetYear,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationActionId(): MitigationActionId
    {
        return $this->mitigationActionId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function targetYear(): int
    {
        return $this->targetYear;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationActionId;
    }

    public function payload(): array
    {
        return [
            'mitigationActionId' => (string) $this->mitigationActionId,
            'name' => $this->name,
            'description' => $this->description,
            'targetYear' => $this->targetYear,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationActionId: new MitigationActionId($payload['mitigationActionId']),
            name: $payload['name'],
            description: $payload['description'],
            targetYear: $payload['targetYear'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
