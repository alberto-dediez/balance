<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Category;

use App\Shared\Domain\Event\DomainEvent;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Domain\Model\AggregateRoot;

class Category implements \Stringable
{
    use AggregateRoot;

    /**
     * @param array<DomainEvent> $events
     */
    public function __construct(
        private CategoryId $id,
        private string $name = '',
        private ?ExternalId $externalId = null,
        private ?bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): CategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function externalId(): ExternalId
    {
        if (!$this->externalId instanceof ExternalId) {
            $this->externalId = new ExternalId();
        }

        return $this->externalId;
    }

    public function isRemoved(): bool
    {
        return $this->removed ?? false;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(string $name, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new CategoryRegistered(
                categoryId: $this->id(),
                name: $name,
                externalId: $externalId,
            ),
        );
    }

    public function update(string $name, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new CategoryUpdated(
                categoryId: $this->id(),
                name: $name,
                externalId: $externalId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CategoryRemoved(
                categoryId: $this->id(),
            ),
        );
    }

    public function applyCategoryRegistered(CategoryRegistered $event): void
    {
        $this->name = $event->name();
        $this->externalId = $event->externalId() ?? new ExternalId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCategoryUpdated(CategoryUpdated $event): void
    {
        $this->name = $event->name();
        $this->externalId = $event->externalId() ?? $this->externalId();
    }

    public function applyCategoryRemoved(CategoryRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
