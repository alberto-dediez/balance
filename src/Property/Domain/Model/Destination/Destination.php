<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Destination;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Destination implements \Stringable
{
    use AggregateRoot;

    /**
     * @param array<DomainEvent> $events
     */
    public function __construct(
        private DestinationId $id,
        private string $name = '',
        private ?CountryId $countryId = null,
        private ?ExternalId $externalId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): DestinationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function countryId(): ?CountryId
    {
        return $this->countryId;
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

    public function register(string $name, CountryId $countryId, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new DestinationRegistered(
                destinationId: $this->id(),
                name: $name,
                countryId: $countryId,
                externalId: $externalId,
            ),
        );
    }

    public function update(string $name, CountryId $countryId, ?ExternalId $externalId = null): void
    {
        $this->recordApplyAndPublish(
            new DestinationUpdated(
                destinationId: $this->id(),
                name: $name,
                countryId: $countryId,
                externalId: $externalId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new DestinationRemoved(
                destinationId: $this->id(),
            ),
        );
    }

    public function applyDestinationRegistered(DestinationRegistered $event): void
    {
        $this->name = $event->name();
        $this->countryId = $event->countryId();
        $this->registeredAt = $event->occurredOn();
        $this->externalId = $event->externalId() ?? new ExternalId();
    }

    public function applyDestinationUpdated(DestinationUpdated $event): void
    {
        $this->name = $event->name();
        $this->countryId = $event->countryId();
        $this->externalId = $event->externalId() ?? $this->externalId();
    }

    public function applyDestinationRemoved(DestinationRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
