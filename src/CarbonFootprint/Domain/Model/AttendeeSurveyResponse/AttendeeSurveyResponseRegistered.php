<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class AttendeeSurveyResponseRegistered extends DomainEvent
{
    protected static string $eventName = 'attendeeSurveyResponseRegistered';

    /** @param list<ResolvedLeg> $legs */
    public function __construct(
        private AttendeeSurveyResponseId $attendeeSurveyResponseId,
        private AttendeeSurveyId $attendeeSurveyId,
        private bool $cameFromAnotherCity,
        private ?TripWaypoint $origin,
        private bool $usedHotel,
        private ?TripWaypoint $accommodation,
        private ?int $hotelNights,
        private ?int $hotelPeople,
        private bool $intercityRoundTrip,
        private ?int $intercityTrips,
        private bool $movedWithinCity,
        private ?TripWaypoint $cityOrigin,
        private bool $cityRoundTrip,
        private ?int $cityTrips,
        private array $legs = [],
        private Emissions $mobilityEmissions = new Emissions(),
        private Emissions $overnightEmissions = new Emissions(),
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private ?int $calculationYear = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function attendeeSurveyResponseId(): AttendeeSurveyResponseId
    {
        return $this->attendeeSurveyResponseId;
    }

    public function attendeeSurveyId(): AttendeeSurveyId
    {
        return $this->attendeeSurveyId;
    }

    public function cameFromAnotherCity(): bool
    {
        return $this->cameFromAnotherCity;
    }

    public function origin(): ?TripWaypoint
    {
        return $this->origin;
    }

    public function usedHotel(): bool
    {
        return $this->usedHotel;
    }

    public function accommodation(): ?TripWaypoint
    {
        return $this->accommodation;
    }

    public function hotelNights(): ?int
    {
        return $this->hotelNights;
    }

    public function hotelPeople(): ?int
    {
        return $this->hotelPeople;
    }

    public function intercityRoundTrip(): bool
    {
        return $this->intercityRoundTrip;
    }

    public function intercityTrips(): ?int
    {
        return $this->intercityTrips;
    }

    public function movedWithinCity(): bool
    {
        return $this->movedWithinCity;
    }

    public function cityOrigin(): ?TripWaypoint
    {
        return $this->cityOrigin;
    }

    public function cityRoundTrip(): bool
    {
        return $this->cityRoundTrip;
    }

    public function cityTrips(): ?int
    {
        return $this->cityTrips;
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

    public function calculationYear(): ?int
    {
        return $this->calculationYear;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->attendeeSurveyResponseId();
    }

    public function payload(): array
    {
        return [
            'attendeeSurveyResponseId' => (string) $this->attendeeSurveyResponseId,
            'attendeeSurveyId' => (string) $this->attendeeSurveyId,
            'cameFromAnotherCity' => $this->cameFromAnotherCity,
            'origin' => self::waypointToPrimitives($this->origin),
            'usedHotel' => $this->usedHotel,
            'accommodation' => self::waypointToPrimitives($this->accommodation),
            'hotelNights' => $this->hotelNights,
            'hotelPeople' => $this->hotelPeople,
            'intercityRoundTrip' => $this->intercityRoundTrip,
            'intercityTrips' => $this->intercityTrips,
            'movedWithinCity' => $this->movedWithinCity,
            'cityOrigin' => self::waypointToPrimitives($this->cityOrigin),
            'cityRoundTrip' => $this->cityRoundTrip,
            'cityTrips' => $this->cityTrips,
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
            attendeeSurveyResponseId: new AttendeeSurveyResponseId($payload['attendeeSurveyResponseId']),
            attendeeSurveyId: new AttendeeSurveyId($payload['attendeeSurveyId']),
            cameFromAnotherCity: $payload['cameFromAnotherCity'],
            origin: self::waypointFromPrimitives($payload['origin'] ?? null),
            usedHotel: $payload['usedHotel'],
            accommodation: self::waypointFromPrimitives($payload['accommodation'] ?? null),
            hotelNights: $payload['hotelNights'] ?? null,
            hotelPeople: null !== ($payload['hotelPeople'] ?? null) ? (int) $payload['hotelPeople'] : null,
            intercityRoundTrip: $payload['intercityRoundTrip'] ?? false,
            intercityTrips: $payload['intercityTrips'] ?? null,
            movedWithinCity: $payload['movedWithinCity'] ?? false,
            cityOrigin: self::waypointFromPrimitives($payload['cityOrigin'] ?? null),
            cityRoundTrip: $payload['cityRoundTrip'] ?? false,
            cityTrips: $payload['cityTrips'] ?? null,
            legs: array_map(self::legFromPrimitives(...), $legs),
            mobilityEmissions: self::emissionsFromPrimitives($mobilityEmissions),
            overnightEmissions: self::emissionsFromPrimitives($overnightEmissions),
            emissionFactorGroupId: null !== ($payload['emissionFactorGroupId'] ?? null)
                ? new EmissionFactorGroupId((string) $payload['emissionFactorGroupId'])
                : null,
            calculationYear: null !== ($payload['calculationYear'] ?? null) ? (int) $payload['calculationYear'] : null,
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
