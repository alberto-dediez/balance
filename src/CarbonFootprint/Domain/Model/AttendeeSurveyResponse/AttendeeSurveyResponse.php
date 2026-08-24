<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;
use Doctrine\Common\Collections\Collection;

class AttendeeSurveyResponse implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<AttendeeSurveyResponseLeg>|Collection<int, AttendeeSurveyResponseLeg> */
    private iterable $legs = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private AttendeeSurveyResponseId $id,
        private ?AttendeeSurveyId $attendeeSurveyId = null,
        private bool $cameFromAnotherCity = false,
        private ?string $originPlaceReference = null,
        private ?string $originLabel = null,
        private ?float $originLatitude = null,
        private ?float $originLongitude = null,
        private bool $usedHotel = false,
        private ?string $accommodationPlaceReference = null,
        private ?string $accommodationLabel = null,
        private ?float $accommodationLatitude = null,
        private ?float $accommodationLongitude = null,
        private ?int $hotelNights = null,
        private bool $intercityRoundTrip = false,
        private ?int $intercityTrips = null,
        private bool $movedWithinCity = false,
        private ?string $cityOriginPlaceReference = null,
        private ?string $cityOriginLabel = null,
        private ?float $cityOriginLatitude = null,
        private ?float $cityOriginLongitude = null,
        private bool $cityRoundTrip = false,
        private ?int $cityTrips = null,
        private ?Emissions $emissions = null,
        private ?Emissions $mobilityEmissions = null,
        private ?Emissions $overnightEmissions = null,
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private ?int $calculationYear = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?ConsentProof $consentProof = null,
    ) {
        $this->events = $events;
    }

    public function id(): AttendeeSurveyResponseId
    {
        return $this->id;
    }

    public function attendeeSurveyId(): ?AttendeeSurveyId
    {
        return $this->attendeeSurveyId;
    }

    public function cameFromAnotherCity(): bool
    {
        return $this->cameFromAnotherCity;
    }

    public function originPlaceReference(): ?string
    {
        return $this->originPlaceReference;
    }

    public function originLabel(): ?string
    {
        return $this->originLabel;
    }

    public function originLatitude(): ?float
    {
        return $this->originLatitude;
    }

    public function originLongitude(): ?float
    {
        return $this->originLongitude;
    }

    public function usedHotel(): bool
    {
        return $this->usedHotel;
    }

    public function accommodationPlaceReference(): ?string
    {
        return $this->accommodationPlaceReference;
    }

    public function accommodationLabel(): ?string
    {
        return $this->accommodationLabel;
    }

    public function accommodationLatitude(): ?float
    {
        return $this->accommodationLatitude;
    }

    public function accommodationLongitude(): ?float
    {
        return $this->accommodationLongitude;
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

    public function cityOriginPlaceReference(): ?string
    {
        return $this->cityOriginPlaceReference;
    }

    public function cityOriginLabel(): ?string
    {
        return $this->cityOriginLabel;
    }

    public function cityOriginLatitude(): ?float
    {
        return $this->cityOriginLatitude;
    }

    public function cityOriginLongitude(): ?float
    {
        return $this->cityOriginLongitude;
    }

    public function cityRoundTrip(): bool
    {
        return $this->cityRoundTrip;
    }

    public function cityTrips(): ?int
    {
        return $this->cityTrips;
    }

    public function emissions(): Emissions
    {
        return $this->emissions ??= new Emissions();
    }

    public function mobilityEmissions(): Emissions
    {
        return $this->mobilityEmissions ??= new Emissions();
    }

    public function overnightEmissions(): Emissions
    {
        return $this->overnightEmissions ??= new Emissions();
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function calculationYear(): ?int
    {
        return $this->calculationYear;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /** @return list<AttendeeSurveyResponseLeg> */
    public function legs(): array
    {
        $legs = $this->legs instanceof Collection ? $this->legs->toArray() : (array) $this->legs;
        $legs = array_values($legs);

        usort(
            $legs,
            static fn(AttendeeSurveyResponseLeg $a, AttendeeSurveyResponseLeg $b): int => [self::kindRank($a->kind()), $a->position()] <=> [self::kindRank($b->kind()), $b->position()],
        );

        return $legs;
    }

    private static function kindRank(LegKind $kind): int
    {
        return LegKind::Intercity === $kind ? 0 : 1;
    }

    /** @return list<AttendeeSurveyResponseLeg> */
    public function legsOfKind(LegKind $kind): array
    {
        return array_values(
            array_filter($this->legs(), static fn(AttendeeSurveyResponseLeg $leg): bool => $leg->kind() === $kind),
        );
    }

    /** @param list<ResolvedLeg> $legs */
    public function register(
        AttendeeSurveyId $attendeeSurveyId,
        bool $cameFromAnotherCity,
        ?TripWaypoint $origin,
        bool $usedHotel,
        ?TripWaypoint $accommodation,
        ?int $hotelNights,
        bool $intercityRoundTrip,
        ?int $intercityTrips,
        bool $movedWithinCity,
        ?TripWaypoint $cityOrigin,
        bool $cityRoundTrip,
        ?int $cityTrips,
        array $legs,
        Emissions $mobilityEmissions = new Emissions(),
        Emissions $overnightEmissions = new Emissions(),
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
        ?int $calculationYear = null,
    ): void {
        $this->recordApplyAndPublish(
            new AttendeeSurveyResponseRegistered(
                attendeeSurveyResponseId: $this->id(),
                attendeeSurveyId: $attendeeSurveyId,
                cameFromAnotherCity: $cameFromAnotherCity,
                origin: $origin,
                usedHotel: $usedHotel,
                accommodation: $accommodation,
                hotelNights: $hotelNights,
                intercityRoundTrip: $intercityRoundTrip,
                intercityTrips: $intercityTrips,
                movedWithinCity: $movedWithinCity,
                cityOrigin: $cityOrigin,
                cityRoundTrip: $cityRoundTrip,
                cityTrips: $cityTrips,
                legs: $legs,
                mobilityEmissions: $mobilityEmissions,
                overnightEmissions: $overnightEmissions,
                emissionFactorGroupId: $emissionFactorGroupId,
                calculationYear: $calculationYear,
            ),
        );
    }

    public function applyAttendeeSurveyResponseRegistered(AttendeeSurveyResponseRegistered $event): void
    {
        $this->attendeeSurveyId = $event->attendeeSurveyId();
        $this->cameFromAnotherCity = $event->cameFromAnotherCity();

        $origin = $event->origin();
        $this->originPlaceReference = $origin?->reference;
        $this->originLabel = $origin?->label;
        $this->originLatitude = $origin?->point?->latitude;
        $this->originLongitude = $origin?->point?->longitude;

        $this->usedHotel = $event->usedHotel();

        $accommodation = $event->accommodation();
        $this->accommodationPlaceReference = $accommodation?->reference;
        $this->accommodationLabel = $accommodation?->label;
        $this->accommodationLatitude = $accommodation?->point?->latitude;
        $this->accommodationLongitude = $accommodation?->point?->longitude;

        $this->hotelNights = $event->hotelNights();
        $this->intercityRoundTrip = $event->intercityRoundTrip();
        $this->intercityTrips = $event->intercityTrips();
        $this->movedWithinCity = $event->movedWithinCity();

        $cityOrigin = $event->cityOrigin();
        $this->cityOriginPlaceReference = $cityOrigin?->reference;
        $this->cityOriginLabel = $cityOrigin?->label;
        $this->cityOriginLatitude = $cityOrigin?->point?->latitude;
        $this->cityOriginLongitude = $cityOrigin?->point?->longitude;

        $this->cityRoundTrip = $event->cityRoundTrip();
        $this->cityTrips = $event->cityTrips();

        $this->mobilityEmissions = $event->mobilityEmissions();
        $this->overnightEmissions = $event->overnightEmissions();
        $this->emissions = $event->mobilityEmissions()->add($event->overnightEmissions());

        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->calculationYear = $event->calculationYear();
        $this->registeredAt = $event->occurredOn();

        $this->legs = array_map(
            fn(ResolvedLeg $leg): AttendeeSurveyResponseLeg => new AttendeeSurveyResponseLeg(
                id: AttendeeSurveyResponseLegId::generate(),
                response: $this,
                position: $leg->position,
                kind: $leg->kind,
                mobilityId: $leg->mobilityId,
                vehicleOccupants: $leg->vehicleOccupants,
                originPlaceReference: $leg->origin->reference,
                originLabel: $leg->origin->label,
                originLatitude: $leg->origin->point?->latitude,
                originLongitude: $leg->origin->point?->longitude,
                destinationPlaceReference: $leg->destination->reference,
                destinationLabel: $leg->destination->label,
                destinationLatitude: $leg->destination->point?->latitude,
                destinationLongitude: $leg->destination->point?->longitude,
                distanceKm: $leg->distanceKm,
                distanceSource: $leg->distanceSource,
            ),
            $event->legs(),
        );
    }

    public function consentProof(): ?ConsentProof
    {
        return $this->consentProof;
    }

    public function hasConsent(): bool
    {
        return null !== $this->consentProof?->consentedAt();
    }

    public function grantConsent(ConsentProof $consentProof): void
    {
        $this->recordApplyAndPublish(new AttendeeSurveyResponseConsentGranted($this->id(), $consentProof));
    }

    public function applyAttendeeSurveyResponseConsentGranted(AttendeeSurveyResponseConsentGranted $event): void
    {
        $this->consentProof = $event->consentProof();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
