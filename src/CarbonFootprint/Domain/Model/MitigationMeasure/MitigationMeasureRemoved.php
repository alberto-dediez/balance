<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationMeasureRemoved extends DomainEvent
{
    protected static string $eventName = 'mitigationMeasureRemoved';

    public function __construct(
        private readonly MitigationMeasureId $mitigationMeasureId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationMeasureId(): MitigationMeasureId
    {
        return $this->mitigationMeasureId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationMeasureId;
    }

    public function payload(): array
    {
        return [
            'mitigationMeasureId' => (string) $this->mitigationMeasureId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationMeasureId: new MitigationMeasureId($payload['mitigationMeasureId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
