<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ExportationExecutionStarted extends DomainEvent
{
    protected static string $eventName = 'exportationExecutionStarted';

    public function __construct(
        private readonly ExportationId $exportationId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function exportationId(): ExportationId
    {
        return $this->exportationId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->exportationId;
    }

    public function payload(): array
    {
        return [
            'exportationId' => (string) $this->exportationId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ExportationExecutionStarted(
            exportationId: new ExportationId($payload['exportationId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
