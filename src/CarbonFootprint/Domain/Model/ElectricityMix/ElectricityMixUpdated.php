<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMixUpdated extends DomainEvent
{
    protected static string $eventName = 'electricityMixUpdated';

    public function __construct(
        private ElectricityMixId $electricityMixId,
        private CountryId $countryId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMixId(): ElectricityMixId
    {
        return $this->electricityMixId;
    }

    public function countryId(): CountryId
    {
        return $this->countryId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMixId();
    }

    public function payload(): array
    {
        return [
            'electricityMixId' => (string) $this->electricityMixId,
            'countryId' => (string) $this->countryId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ElectricityMixUpdated(
            electricityMixId: new ElectricityMixId($payload['electricityMixId']),
            countryId: new CountryId($payload['countryId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
