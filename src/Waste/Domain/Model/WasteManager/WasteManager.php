<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\WasteManager;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class WasteManager implements \Stringable
{
    use AggregateRoot;

    /**
     * @param array<mixed> $events
     */
    public function __construct(
        private WasteManagerId $id,
        private string $name = '',
        private ?AccountId $accountId = null,
        private bool $isPublic = true,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable(),
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): WasteManagerId
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name,
        bool $isPublic,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new WasteManagerRegistered(
                wasteManagerId: $this->id(),
                name: $name,
                isPublic: $isPublic,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        bool $isPublic,
    ): void {
        $this->recordApplyAndPublish(
            new WasteManagerUpdated(
                wasteManagerId: $this->id(),
                name: $name,
                isPublic: $isPublic,
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
        $this->isPublic = $event->isPublic();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteManagerUpdated(WasteManagerUpdated $event): void
    {
        $this->name = $event->name();
        $this->isPublic = $event->isPublic();
    }

    public function applyWasteManagerRemoved(WasteManagerRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable('NOW');
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
