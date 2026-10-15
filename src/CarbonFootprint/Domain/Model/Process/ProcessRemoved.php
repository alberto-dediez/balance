<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProcessRemoved extends DomainEvent
{
    protected static string $eventName = 'processRemoved';

    public function __construct(
        private ProcessId $processId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function processId(): ProcessId
    {
        return $this->processId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->processId();
    }

    public function payload(): array
    {
        return [
            'processId' => (string) $this->processId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProcessRemoved(
            processId: new ProcessId($payload['processId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
