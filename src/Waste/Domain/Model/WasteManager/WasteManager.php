<?php

namespace App\Waste\Domain\Model\WasteManager;

use App\Shared\Domain\Model\AggregateRoot;
use App\Security\Domain\Model\Account\AccountId;

class WasteManager
{
    use AggregateRoot;

    public function __construct(
        private WasteManagerId $id,
        private ?string $name = null,
        private AccountId|null $accountId = null,
        private bool $isPublic = true,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

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
        AccountId $accountId
    ) {
        $this->recordApplyAndPublish(
            new WasteManagerRegistered(
                wasteManagerId: $this->id(),
                name: $name,
                isPublic: $isPublic,
                accountId: $accountId
            )
        );
    }

    public function update(
        string $name,
        bool $isPublic
    ) {
        $this->recordApplyAndPublish(
            new WasteManagerUpdated(
                wasteManagerId: $this->id(),
                name: $name,
                isPublic: $isPublic
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new WasteManagerRemoved(
                wasteManagerId: $this->id()
            )
        );
    }

    public function applyWasteManagerRegistered(WasteManagerRegistered $event)
    {
        $this->name = $event->name();
        $this->isPublic = $event->isPublic();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteManagerUpdated(WasteManagerUpdated $event)
    {
        $this->name = $event->name();
        $this->isPublic = $event->isPublic();
    }

    public function applyWasteManagerRemoved(WasteManagerRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable("NOW");
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
