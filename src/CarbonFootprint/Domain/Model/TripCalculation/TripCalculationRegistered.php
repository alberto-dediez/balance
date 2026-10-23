<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class TripCalculationRegistered extends DomainEvent
{
    protected static string $eventName = 'tripCalculationRegistered';

    /** @param list<ResolvedLeg> $legs */
    public function __construct(
        private TripCalculationId $tripCalculationId,
        private TripCalculationLinkId $tripCalculationLinkId,
        private ?TripWaypoint $origin,
        private ?TripWaypoint $destination,
        private bool $roundTrip,
        private int $trips,
        private ?int $nights,
        private ?int $people,
        private array $legs,
        private Emissions $mobilityEmissions,
        private Emissions $overnightEmissions,
        private ?EmissionFactorGroupId $emissionFactorGroupId,
        private int $calculationYear,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function tripCalculationId(): TripCalculationId
    {
        return $this->tripCalculationId;
    }

    public function tripCalculationLinkId(): TripCalculationLinkId
    {
        return $this->tripCalculationLinkId;
    }

    public function origin(): ?TripWaypoint
    {
        return $this->origin;
    }

    public function destination(): ?TripWaypoint
    {
        return $this->destination;
    }

    public function roundTrip(): bool
    {
        return $this->roundTrip;
    }

    public function trips(): int
    {
        return $this->trips;
    }

    public function nights(): ?int
    {
        return $this->nights;
    }

    public function people(): ?int
    {
        return $this->people;
    }

    /** @return list<ResolvedLeg> */
    public function legs(): array
    {
        return $this->legs;
    }

    public function mobilityEmissions(): Emissions
    {
        return $this->mobilityEmissions;
    }

    public function overnightEmissions(): Emissions
    {
        return $this->overnightEmissions;
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function calculationYear(): int
    {
        return $this->calculationYear;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->tripCalculationId();
    }

    public function payload(): array
    {
        return [
            'tripCalculationId' => (string) $this->tripCalculationId,
            'tripCalculationLinkId' => (string) $this->tripCalculationLinkId,
            'origin' => self::waypointToPrimitives($this->origin),
            'destination' => self::waypointToPrimitives($this->destination),
            'roundTrip' => $this->roundTrip,
            'trips' => $this->trips,
            'nights' => $this->nights,
            'people' => $this->people,
            'legs' => array_map(self::legToPrimitives(...), $this->legs),
            'mobilityEmissions' => self::emissionsToPrimitives($this->mobilityEmissions),
            'overnightEmissions' => self::emissionsToPrimitives($this->overnightEmissions),
            'emissionFactorGroupId' => null !== $this->emissionFactorGroupId ? (string) $this->emissionFactorGroupId : null,
            'calculationYear' => $this->calculationYear,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        /** @var list<array<string, mixed>> $legs */
        $legs = $payload['legs'] ?? [];

        /** @var array<string, mixed>|null $mobilityEmissions */
        $mobilityEmissions = $payload['mobilityEmissions'] ?? null;

        /** @var array<string, mixed>|null $overnightEmissions */
        $overnightEmissions = $payload['overnightEmissions'] ?? null;

        return new self(
            tripCalculationId: new TripCalculationId($payload['tripCalculationId']),
            tripCalculationLinkId: new TripCalculationLinkId($payload['tripCalculationLinkId']),
            origin: self::waypointFromPrimitives($payload['origin'] ?? null),
            destination: self::waypointFromPrimitives($payload['destination'] ?? null),
            roundTrip: $payload['roundTrip'] ?? false,
            trips: (int) ($payload['trips'] ?? 1),
            nights: null !== ($payload['nights'] ?? null) ? (int) $payload['nights'] : null,
            people: null !== ($payload['people'] ?? null) ? (int) $payload['people'] : null,
            legs: array_map(self::legFromPrimitives(...), $legs),
            mobilityEmissions: self::emissionsFromPrimitives($mobilityEmissions),
            overnightEmissions: self::emissionsFromPrimitives($overnightEmissions),
            emissionFactorGroupId: null !== ($payload['emissionFactorGroupId'] ?? null)
                ? new EmissionFactorGroupId((string) $payload['emissionFactorGroupId'])
                : null,
            calculationYear: (int) ($payload['calculationYear'] ?? 0),
            id: $id,
            occurredOn: $occurredOn,
        );
    }

    /** @return array<string, string> */
    private static function emissionsToPrimitives(Emissions $emissions): array
    {
        return [
            'co2eq' => $emissions->co2eq(),
            'co2' => $emissions->co2(),
            'ch4' => $emissions->ch4(),
            'n2o' => $emissions->n2o(),
        ];
    }

    /** @param array<string, mixed>|null $payload */
    private static function emissionsFromPrimitives(?array $payload): Emissions
    {
        if (null === $payload) {
            return new Emissions();
        }

        return new Emissions(
            co2eq: (string) ($payload['co2eq'] ?? '0'),
            co2: (string) ($payload['co2'] ?? '0'),
            ch4: (string) ($payload['ch4'] ?? '0'),
            n2o: (string) ($payload['n2o'] ?? '0'),
        );
    }

    /** @return array<string, mixed>|null */
    private static function waypointToPrimitives(?TripWaypoint $waypoint): ?array
    {
        if (null === $waypoint) {
            return null;
        }

        return [
            'reference' => $waypoint->reference,
            'label' => $waypoint->label,
            'latitude' => $waypoint->point?->latitude,
            'longitude' => $waypoint->point?->longitude,
        ];
    }

    /** @param array<string, mixed>|null $payload */
    private static function waypointFromPrimitives(?array $payload): ?TripWaypoint
    {
        if (null === $payload) {
            return null;
        }

        $latitude = $payload['latitude'] ?? null;
        $longitude = $payload['longitude'] ?? null;

        return new TripWaypoint(
            reference: null !== ($payload['reference'] ?? null) ? (string) $payload['reference'] : null,
            label: null !== ($payload['label'] ?? null) ? (string) $payload['label'] : null,
            point: is_numeric($latitude) && is_numeric($longitude) ? new GeoPoint((float) $latitude, (float) $longitude) : null,
        );
    }

    /** @return array<string, mixed> */
    private static function legToPrimitives(ResolvedLeg $leg): array
    {
        return [
            'position' => $leg->position,
            'kind' => $leg->kind->value,
            'mobilityId' => (string) $leg->mobilityId,
            'vehicleOccupants' => $leg->vehicleOccupants,
            'origin' => self::waypointToPrimitives($leg->origin),
            'destination' => self::waypointToPrimitives($leg->destination),
            'distanceKm' => $leg->distanceKm,
            'distanceSource' => $leg->distanceSource->value,
        ];
    }

    /** @param array<string, mixed> $payload */
    private static function legFromPrimitives(array $payload): ResolvedLeg
    {
        /** @var array<string, mixed>|null $origin */
        $origin = $payload['origin'] ?? null;

        /** @var array<string, mixed>|null $destination */
        $destination = $payload['destination'] ?? null;

        return new ResolvedLeg(
            position: (int) $payload['position'],
            kind: LegKind::from((string) $payload['kind']),
            mobilityId: new MobilityId((string) $payload['mobilityId']),
            vehicleOccupants: null !== ($payload['vehicleOccupants'] ?? null) ? (int) $payload['vehicleOccupants'] : null,
            origin: self::waypointFromPrimitives($origin) ?? new TripWaypoint(),
            destination: self::waypointFromPrimitives($destination) ?? new TripWaypoint(),
            distanceKm: null !== ($payload['distanceKm'] ?? null) ? (float) $payload['distanceKm'] : null,
            distanceSource: DistanceSource::from((string) $payload['distanceSource']),
        );
    }
}
