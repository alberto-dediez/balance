<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DoggyBagExceptionRegistered extends DomainEvent
{
    protected static string $eventName = 'doggyBagExceptionRegistered';

    public function __construct(
        private DoggyBagExceptionId $doggyBagExceptionId,
        private string $description,
        private CriticalPointId $criticalPointId,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function doggyBagExceptionId(): DoggyBagExceptionId
    {
        return $this->doggyBagExceptionId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->doggyBagExceptionId();
    }

    public function payload(): array
    {
        return [
            'doggyBagExceptionId' => (string) $this->doggyBagExceptionId,
            'description' => $this->description,
            'criticalPointId' => (string) $this->criticalPointId,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DoggyBagExceptionRegistered(
            doggyBagExceptionId: new DoggyBagExceptionId($payload['doggyBagExceptionId']),
            description: $payload['description'],
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
