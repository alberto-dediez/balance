<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ImportationRegistered extends DomainEvent
{
    protected static string $eventName = 'importationRegistered';

    public function __construct(
        private readonly ImportationId $importationId,
        private readonly string $name,
        private readonly string $type,
        private readonly ?AccountId $accountId,
        private readonly ?UserId $registeredBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    public function type(): string
    {
        return $this->type;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->importationId;
    }

    public function payload(): array
    {
        return [
            'importationId' => (string) $this->importationId,
            'accountId' => $this->accountId instanceof AccountId ? (string) $this->accountId : null,
            'registeredBy' => $this->registeredBy instanceof UserId ? (string) $this->registeredBy : null,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ImportationRegistered(
            importationId: new ImportationId($payload['importationId']),
            name: $payload['name'],
            type: $payload['type'],
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            registeredBy: null !== $payload['registeredBy'] ? new UserId($payload['registeredBy']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
