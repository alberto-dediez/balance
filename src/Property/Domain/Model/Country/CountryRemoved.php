<?php

namespace App\Property\Domain\Model\Country;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Property\Domain\Model\Country\CountryId;

final class CountryRemoved extends DomainEvent
{
    protected static string $eventName = 'countryRemoved';

    public function __construct(
        private CountryId $countryId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function countryId(): CountryId
    {
        return $this->countryId;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->countryId();
    }
    public function payload(): ?array
    {
        return [
            'countryId' => (string) $this->countryId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new CountryRemoved(
            countryId: new CountryId($payload['countryId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}