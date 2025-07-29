<?php

namespace App\Property\Domain\Model\Country;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;

final class CountryRegistered extends DomainEvent
{
    protected static string $eventName = 'countryRegistered';

    public function __construct(
        private CountryId $countryId, 
        private string $name, 
        private ?string $iso = null, 
        private ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function countryId(): CountryId
    {
        return $this->countryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function iso(): ?string
    {
        return $this->iso;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function payload(): ?array
    {
        return [
            'countryId' => (string) $this->countryId,
            'iso' => $this->iso(),
            'name' => $this->name,
            'externalId' => $this->externalId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new CountryRegistered(
            countryId: new CountryId($payload['countryId']), 
            iso:$payload['iso'], 
            name: $payload['name'],
            externalId: isset($payload['externalId']) ? $payload['externalId'] : null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}