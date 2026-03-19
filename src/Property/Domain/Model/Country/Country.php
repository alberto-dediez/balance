<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Country;

use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Domain\Model\AggregateRoot;

class Country implements \Stringable
{
    use AggregateRoot;

    public function __construct(
        private CountryId $id,
        private string $name = '',
        private ?string $iso = null,
        private ?ExternalId $externalId = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {}

    public function id(): CountryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function iso(): ?string
    {
        return $this->iso;
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

    public function register(string $name, ?string $iso, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new CountryRegistered(
                countryId: $this->id(),
                name: $name,
                iso: $iso,
                externalId: $externalId,
            ),
        );
    }

    public function update(string $name, ?string $iso, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new CountryUpdated(
                countryId: $this->id(),
                name: $name,
                iso: $iso,
                externalId: $externalId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CountryRemoved(
                countryId: $this->id(),
            ),
        );
    }

    public function applyCountryRegistered(CountryRegistered $event): void
    {
        $this->name = $event->name();
        $this->iso = $event->iso();
        $this->registeredAt = $event->occurredOn();
        $this->externalId = $event->externalId() ?? new ExternalId();
    }

    public function applyCountryUpdated(CountryUpdated $event): void
    {
        $this->name = $event->name();
        $this->iso = $event->iso();
        $this->externalId = $event->externalId() ?? $this->externalId();
    }

    public function applyCountryRemoved(CountryRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
