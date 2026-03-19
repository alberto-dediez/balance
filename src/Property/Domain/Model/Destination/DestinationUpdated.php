<?php

namespace App\Property\Domain\Model\Destination;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Destination\DestinationId;

final class DestinationUpdated extends DomainEvent
{
    protected static string $eventName = 'destinationUpdated';

    public function __construct(
        private readonly DestinationId $destinationId,
        private readonly string $name,
        private readonly ?CountryId $countryId = null,
        private readonly ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function destinationId(): DestinationId
    {
        return $this->destinationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function countryId(): ?CountryId
    {
        return $this->countryId;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->destinationId;
    }

    public function payload(): array
    {
        return [
            'destinationId' => (string) $this->destinationId,
            'countryId' => (string) $this->countryId,
            'name' => $this->name,
            'externalId' => $this->externalId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new DestinationUpdated(
            destinationId: new DestinationId($payload['destinationId']),
            name: $payload['name'],
            countryId: null == $payload['countryId'] ? CountryId::generate() : new CountryId($payload['countryId']),
            externalId: $payload['externalId'] ?? null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
