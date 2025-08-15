<?php

namespace App\Waste\Domain\Model\WasteManager;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class WasteManagerRegistered extends DomainEvent
{
    protected static string $eventName = 'wasteManagerRegistered';

    public function __construct(
        private WasteManagerId $wasteManagerId, 
        private string $name,
        private bool $isPublic,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->wasteManagerId();
    }
    
    public function payload(): ?array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId,
            'accountId' => (string) $this->accountId,
            'name' => $this->name,
            'isPublic' => $this->isPublic
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new WasteManagerRegistered(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']), 
            accountId: new AccountId($payload['accountId']),
            name: $payload['name'],
            isPublic: $payload['isPublic'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}