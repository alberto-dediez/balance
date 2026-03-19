<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ImportationDefaultValuesUpdated extends DomainEvent
{
    protected static string $eventName = 'importationDefaultValuesUpdated';

    public function __construct(
        private readonly ImportationId $importationId,
        private readonly array $defaultValues = [],
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function importationId(): ImportationId
    {
        return $this->importationId;
    }

    public function defaultValues(): array
    {
        return $this->defaultValues;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->importationId;
    }

    public function payload(): array
    {
        return [
            'importationId' => (string) $this->importationId,
            'defaultValues' => $this->defaultValues,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ImportationDefaultValuesUpdated(
            importationId: new ImportationId($payload['importationId']),
            defaultValues: $payload['defaultValues'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
