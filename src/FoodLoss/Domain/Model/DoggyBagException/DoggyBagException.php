<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class DoggyBagException implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private DoggyBagExceptionId $id,
        private string $description = '',
        private ?CriticalPointId $criticalPointId = null,
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): DoggyBagExceptionId
    {
        return $this->id;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function criticalPointId(): ?CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $description,
        CriticalPointId $criticalPointId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new DoggyBagExceptionRegistered(
                doggyBagExceptionId: $this->id(),
                description: $description,
                criticalPointId: $criticalPointId,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $description,
        CriticalPointId $criticalPointId,
    ): void {
        $this->recordApplyAndPublish(
            new DoggyBagExceptionUpdated(
                doggyBagExceptionId: $this->id(),
                description: $description,
                criticalPointId: $criticalPointId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new DoggyBagExceptionRemoved(
                doggyBagExceptionId: $this->id(),
            ),
        );
    }

    public function applyDoggyBagExceptionRegistered(DoggyBagExceptionRegistered $event): void
    {
        $this->description = $event->description();
        $this->criticalPointId = $event->criticalPointId();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyDoggyBagExceptionUpdated(DoggyBagExceptionUpdated $event): void
    {
        $this->description = $event->description();
        $this->criticalPointId = $event->criticalPointId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDoggyBagExceptionRemoved(DoggyBagExceptionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->description();
    }
}
