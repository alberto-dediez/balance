<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProcessRegistered extends DomainEvent
{
    protected static string $eventName = 'processRegistered';

    public function __construct(
        private ProcessId $processId,
        private string $name,
        private Unit $unit,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function processId(): ProcessId
    {
        return $this->processId;
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
        return $this->processId();
    }

    public function payload(): array
    {
        return [
            'processId' => (string) $this->processId,
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

        return new ProcessRegistered(
            processId: new ProcessId($payload['processId']),
            name: $payload['name'],
            unit: $unit,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
