<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UseProfileUpdated extends DomainEvent
{
    protected static string $eventName = 'useProfileUpdated';

    public function __construct(
        private UseProfileId $useProfileId,
        private string $name,
        private Unit $unit,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function useProfileId(): UseProfileId
    {
        return $this->useProfileId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->useProfileId();
    }

    public function payload(): array
    {
        return [
            'useProfileId' => (string) $this->useProfileId,
            'name' => $this->name,
            'unit' => $this->unit->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new UseProfileUpdated(
            useProfileId: new UseProfileId($payload['useProfileId']),
            name: $payload['name'],
            unit: $unit,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
