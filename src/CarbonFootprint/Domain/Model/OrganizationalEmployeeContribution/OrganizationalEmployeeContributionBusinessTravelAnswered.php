<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalEmployeeContributionBusinessTravelAnswered extends DomainEvent
{
    protected static string $eventName = 'organizationalEmployeeContributionBusinessTravelAnswered';

    /** @param list<TripAnswer> $trips */
    public function __construct(
        private OrganizationalEmployeeContributionId $contributionId,
        private array $trips,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalEmployeeContributionId
    {
        return $this->contributionId;
    }

    /** @return list<TripAnswer> */
    public function trips(): array
    {
        return $this->trips;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId;
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'trips' => array_map(
                static fn(TripAnswer $trip): array => [
                    'mobilityId' => (string) $trip->mobilityId,
                    'distanceKm' => $trip->distanceKm,
                    'trips' => $trip->trips,
                    'vehicleOccupants' => $trip->vehicleOccupants,
                    'hotelNights' => $trip->hotelNights,
                ],
                $this->trips,
            ),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OrganizationalEmployeeContributionId($payload['contributionId']),
            trips: array_values(array_map(
                static fn(array $trip): TripAnswer => new TripAnswer(
                    mobilityId: new MobilityId($trip['mobilityId']),
                    distanceKm: (float) $trip['distanceKm'],
                    trips: (int) $trip['trips'],
                    vehicleOccupants: (int) $trip['vehicleOccupants'],
                    hotelNights: (int) $trip['hotelNights'],
                ),
                $payload['trips'] ?? [],
            )),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
