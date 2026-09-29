<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationActionRemoved extends DomainEvent
{
    protected static string $eventName = 'mitigationActionRemoved';

    public function __construct(
        private readonly MitigationActionId $mitigationActionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationActionId(): MitigationActionId
    {
        return $this->mitigationActionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationActionId;
    }

    public function payload(): array
    {
        return [
            'mitigationActionId' => (string) $this->mitigationActionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationActionId: new MitigationActionId($payload['mitigationActionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
