<?php

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use DateTimeImmutable;

final class ImportationRegistered extends DomainEvent
{
    protected static string $eventName = 'importationRegistered';

    public function __construct(
        private ImportationId $importationId,
        private string $name,
        private ?AccountId $accountId,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function importationId(): ImportationId
    {
        return $this->importationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->importationId();
    }

    public function payload(): ?array
    {
        return [
            'importationId' => (string) $this->importationId,
            'accountId' => null === $this->accountId ? null : (string) $this->accountId,
            'name' => (string) $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ImportationRegistered(
            importationId: new ImportationId($payload['importationId']),
            name: $payload['name'],
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
