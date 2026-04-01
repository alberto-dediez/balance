<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointRegistered extends DomainEvent
{
    protected static string $eventName = 'criticalPointRegistered';

    public function __construct(
        private CriticalPointId $criticalPointId,
        private string $name,
        private PropertyId $propertyId,
        private AccountId $accountId,
        private SupplyChainStage $supplyChainStage,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function supplyChainStage(): SupplyChainStage
    {
        return $this->supplyChainStage;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId();
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'name' => $this->name,
            'propertyId' => (string) $this->propertyId,
            'accountId' => (string) $this->accountId,
            'supplyChainStage' => $this->supplyChainStage->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointRegistered(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            name: $payload['name'],
            propertyId: new PropertyId($payload['propertyId']),
            accountId: new AccountId($payload['accountId']),
            supplyChainStage: SupplyChainStage::from($payload['supplyChainStage']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
