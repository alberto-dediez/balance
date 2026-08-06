<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
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
        private bool $intercityRoundTrip,
        private ?int $intercityTrips,
        private bool $movedWithinCity,
        private ?TripWaypoint $cityOrigin,
        private bool $cityRoundTrip,
        private ?int $cityTrips,
        private array $legs = [],
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
            'intercityRoundTrip' => $this->intercityRoundTrip,
            'intercityTrips' => $this->intercityTrips,
            'movedWithinCity' => $this->movedWithinCity,
            'cityOrigin' => self::waypointToPrimitives($this->cityOrigin),
            'cityRoundTrip' => $this->cityRoundTrip,
            'cityTrips' => $this->cityTrips,
            'legs' => array_map(self::legToPrimitives(...), $this->legs),
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        /** @var list<array<string, mixed>> $legs */
        $legs = $payload['legs'] ?? [];

        return new self(
            attendeeSurveyResponseId: new AttendeeSurveyResponseId($payload['attendeeSurveyResponseId']),
            attendeeSurveyId: new AttendeeSurveyId($payload['attendeeSurveyId']),
            cameFromAnotherCity: $payload['cameFromAnotherCity'],
            origin: self::waypointFromPrimitives($payload['origin'] ?? null),
            usedHotel: $payload['usedHotel'],
            accommodation: self::waypointFromPrimitives($payload['accommodation'] ?? null),
            hotelNights: $payload['hotelNights'] ?? null,
            intercityRoundTrip: $payload['intercityRoundTrip'] ?? false,
            intercityTrips: $payload['intercityTrips'] ?? null,
            movedWithinCity: $payload['movedWithinCity'] ?? false,
            cityOrigin: self::waypointFromPrimitives($payload['cityOrigin'] ?? null),
            cityRoundTrip: $payload['cityRoundTrip'] ?? false,
            cityTrips: $payload['cityTrips'] ?? null,
            legs: array_map(self::legFromPrimitives(...), $legs),
            id: $id,
            occurredOn: $occurredOn,
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
