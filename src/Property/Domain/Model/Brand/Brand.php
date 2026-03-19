<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Brand;

use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Domain\Model\AggregateRoot;

class Brand implements \Stringable
{
    use AggregateRoot;

    public function __construct(
        private BrandId $id,
        private string $name = '',
        private ?ExternalId $externalId = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {}

    public function id(): BrandId
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

    public function register(string $name, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new BrandRegistered(
                brandId: $this->id(),
                name: $name,
                externalId: $externalId,
            ),
        );
    }

    public function update(string $name, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new BrandUpdated(
                brandId: $this->id(),
                name: $name,
                externalId: $externalId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new BrandRemoved(
                brandId: $this->id(),
            ),
        );
    }

    public function applyBrandRegistered(BrandRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
        $this->externalId = $event->externalId() ?? new ExternalId();
    }

    public function applyBrandUpdated(BrandUpdated $event): void
    {
        $this->name = $event->name();
        $this->externalId = $event->externalId() ?? $this->externalId();
    }

    public function applyBrandRemoved(BrandRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
