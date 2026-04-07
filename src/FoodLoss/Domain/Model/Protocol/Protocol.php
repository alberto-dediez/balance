<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Protocol implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private ProtocolId $id,
        private ?AccountId $accountId = null,
        private string $name = '',
        private string $description = '',
        private string $path = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): ProtocolId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function path(): string
    {
        return $this->path;
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
        string $description,
        string $path,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new ProtocolRegistered(
                protocolId: $this->id(),
                name: $name,
                description: $description,
                path: $path,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        string $description,
        string $path,
    ): void {
        $this->recordApplyAndPublish(
            new ProtocolUpdated(
                protocolId: $this->id(),
                name: $name,
                description: $description,
                path: $path,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ProtocolRemoved(
                protocolId: $this->id(),
            ),
        );
    }

    public function applyProtocolRegistered(ProtocolRegistered $event): void
    {
        $this->name = $event->name();
        $this->description = $event->description();
        $this->path = $event->path();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyProtocolUpdated(ProtocolUpdated $event): void
    {
        $this->name = $event->name();
        $this->description = $event->description();
        $this->path = $event->path();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProtocolRemoved(ProtocolRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
