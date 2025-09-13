<?php

namespace App\Property\Domain\Model\Destination;

use App\Shared\Domain\Model\AggregateRoot;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;

class Destination
{
    use AggregateRoot;

    public function __construct(
        private DestinationId $id,
        private string $name = '',
        private CountryId|null $countryId = null,
        private ?ExternalId $externalId = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

    }

    public function id(): DestinationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function countryId(): CountryId
    {
        return $this->countryId;
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

    public function register(string $name, CountryId $countryId, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new DestinationRegistered(
                destinationId: $this->id(),
                countryId: $countryId,
                name: $name,
                externalId: $externalId
            )
        );
    }

    public function update(string $name, CountryId $countryId, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new DestinationUpdated(
                destinationId: $this->id(),
                countryId: $countryId,
                name: $name,
                externalId: $externalId
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new DestinationRemoved(
                destinationId: $this->id()
            )
        );
    }

    public function applyDestinationRegistered(DestinationRegistered $event)
    {
        $this->name = $event->name();
        $this->countryId = $event->countryId();
        $this->registeredAt = $event->occurredOn();
        $this->externalId = null === $event->externalId() ? new ExternalId() : $event->externalId();
    }

    public function applyDestinationUpdated(DestinationUpdated $event)
    {
        $this->name = $event->name();
        $this->countryId = $event->countryId();
        $this->externalId = null === $event->externalId() ? $this->externalId() : $event->externalId();
    }

    public function applyDestinationRemoved(DestinationRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
