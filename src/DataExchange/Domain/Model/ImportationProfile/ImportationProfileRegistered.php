<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\ImportationProfile;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ImportationProfileRegistered extends DomainEvent
{
    protected static string $eventName = 'importationProfileRegistered';

    public function __construct(
        private readonly ImportationProfileId $importationProfileId,
        private readonly ?AccountId $accountId,
        private readonly string $type,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function importationProfileId(): ImportationProfileId
    {
        return $this->importationProfileId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->importationProfileId;
    }

    public function payload(): array
    {
        return [
            'importationProfileId' => (string) $this->importationProfileId,
            'accountId' => $this->accountId instanceof AccountId ? (string) $this->accountId : null,
            'type' => $this->type,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ImportationProfileRegistered(
            importationProfileId: new ImportationProfileId($payload['importationProfileId']),
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            type: $payload['type'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
