<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\WasteManager;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class WasteManager implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private WasteManagerId $id,
        private string $name = '',
        private bool $animalFeedAuthorized = false,
        private bool $compostAuthorized = false,
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): WasteManagerId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function animalFeedAuthorized(): bool
    {
        return $this->animalFeedAuthorized;
    }

    public function compostAuthorized(): bool
    {
        return $this->compostAuthorized;
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
        string $name,
        bool $animalFeedAuthorized,
        bool $compostAuthorized,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new WasteManagerRegistered(
                wasteManagerId: $this->id(),
                name: $name,
                animalFeedAuthorized: $animalFeedAuthorized,
                compostAuthorized: $compostAuthorized,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        bool $animalFeedAuthorized,
        bool $compostAuthorized,
    ): void {
        $this->recordApplyAndPublish(
            new WasteManagerUpdated(
                wasteManagerId: $this->id(),
                name: $name,
                animalFeedAuthorized: $animalFeedAuthorized,
                compostAuthorized: $compostAuthorized,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new WasteManagerRemoved(
                wasteManagerId: $this->id(),
            ),
        );
    }

    public function applyWasteManagerRegistered(WasteManagerRegistered $event): void
    {
        $this->name = $event->name();
        $this->animalFeedAuthorized = $event->animalFeedAuthorized();
        $this->compostAuthorized = $event->compostAuthorized();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteManagerUpdated(WasteManagerUpdated $event): void
    {
        $this->name = $event->name();
        $this->animalFeedAuthorized = $event->animalFeedAuthorized();
        $this->compostAuthorized = $event->compostAuthorized();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyWasteManagerRemoved(WasteManagerRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
