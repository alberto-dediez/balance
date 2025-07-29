<?php

namespace App\Property\Domain\Model\Category;

use App\Shared\Domain\Model\AggregateRoot;
use App\Property\Domain\Model\Property\ExternalId;

class Category
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private CategoryId $id, 
        private string $name = '',
        private ?ExternalId $externalId = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

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
        if (null === $this->externalId) {
            $this->externalId = new ExternalId();
        }

        return $this->externalId;
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

    public function register(string $name, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new CategoryRegistered(
                categoryId: $this->id(),
                name: $name,
                externalId: $externalId
            )
        );
    }

    public function update(string $name, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new CategoryUpdated(
                categoryId: $this->id(),
                name: $name,
                externalId: $externalId
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new CategoryRemoved(
                categoryId: $this->id()
            )
        );
    }

    public function applyCategoryRegistered(CategoryRegistered $event)
    {
        $this->name = $event->name();
        $this->externalId = null === $event->externalId() ? new ExternalId() : $event->externalId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCategoryUpdated(CategoryUpdated $event)
    {
        $this->name = $event->name();
        $this->externalId = null === $event->externalId() ? $this->externalId() : $event->externalId();
    }

    public function applyCategoryRemoved(CategoryRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}