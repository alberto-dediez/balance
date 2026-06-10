<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ExportationFailed extends DomainEvent
{
    protected static string $eventName = 'exportationFailed';

    public function __construct(
        private readonly ExportationId $exportationId,
        private readonly string $errorMessage,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function exportationId(): ExportationId
    {
        return $this->exportationId;
    }

    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->exportationId;
    }

    public function payload(): array
    {
        return [
            'exportationId' => (string) $this->exportationId,
            'errorMessage' => $this->errorMessage,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ExportationFailed(
            exportationId: new ExportationId($payload['exportationId']),
            errorMessage: $payload['errorMessage'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
