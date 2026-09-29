<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationMeasureCategoryRegistered extends DomainEvent
{
    protected static string $eventName = 'mitigationMeasureCategoryRegistered';

    public function __construct(
        private readonly MitigationMeasureCategoryId $mitigationMeasureCategoryId,
        private readonly string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationMeasureCategoryId(): MitigationMeasureCategoryId
    {
        return $this->mitigationMeasureCategoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationMeasureCategoryId;
    }

    public function payload(): array
    {
        return [
            'mitigationMeasureCategoryId' => (string) $this->mitigationMeasureCategoryId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationMeasureCategoryId: new MitigationMeasureCategoryId($payload['mitigationMeasureCategoryId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
