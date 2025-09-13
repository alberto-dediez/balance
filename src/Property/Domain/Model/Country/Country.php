<?php

namespace App\Property\Domain\Model\Country;

use App\Shared\Domain\Model\AggregateRoot;
use App\Property\Domain\Model\Property\ExternalId;

class Country
{
    use AggregateRoot;

    public function __construct(
        private CountryId $id,
        private string $name = '',
        private string|null $iso = null,
        private ?ExternalId $externalId = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

    }

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

    public function register(string $name, ?string $iso, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new CountryRegistered(
                countryId: $this->id(),
                iso: $iso,
                name: $name,
                externalId: $externalId
            )
        );
    }

    public function update(string $name, ?string $iso, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new CountryUpdated(
                countryId: $this->id(),
                iso: $iso,
                name: $name,
                externalId: $externalId
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new CountryRemoved(
                countryId: $this->id()
            )
        );
    }

    public function applyCountryRegistered(CountryRegistered $event)
    {
        $this->name = $event->name();
        $this->iso = $event->iso();
        $this->registeredAt = $event->occurredOn();
        $this->externalId = null === $event->externalId() ? new ExternalId() : $event->externalId();
    }

    public function applyCountryUpdated(CountryUpdated $event)
    {
        $this->name = $event->name();
        $this->iso = $event->iso();
        $this->externalId = null === $event->externalId() ? $this->externalId() : $event->externalId();
    }

    public function applyCountryRemoved(CountryRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
