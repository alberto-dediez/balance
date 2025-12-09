<?php

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use DateTimeImmutable;

final class ImportationExecutionStarted extends DomainEvent
{
    protected static string $eventName = 'importationExecutionStarted';

    public function __construct(
        private ImportationId $importationId,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function importationId(): ImportationId
    {
        return $this->importationId;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->importationId();
    }

    public function payload(): ?array
    {
        return [
            'importationId' => (string) $this->importationId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ImportationExecutionStarted(
            importationId: new ImportationId($payload['importationId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
