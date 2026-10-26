<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;
use Doctrine\Common\Collections\Collection;

class TripCalculation implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<TripCalculationLeg>|Collection<int, TripCalculationLeg> */
    private iterable $legs = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private TripCalculationId $id,
        private ?TripCalculationLinkId $tripCalculationLinkId = null,
        private ?string $originPlaceReference = null,
        private ?string $originLabel = null,
        private ?float $originLatitude = null,
        private ?float $originLongitude = null,
        private bool $roundTrip = false,
        private ?int $trips = null,
        private ?int $nights = null,
        private ?int $people = null,
        private ?Emissions $emissions = null,
        private ?Emissions $mobilityEmissions = null,
        private ?Emissions $overnightEmissions = null,
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private ?int $calculationYear = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?ConsentProof $consentProof = null,
        private ?string $destinationPlaceReference = null,
        private ?string $destinationLabel = null,
        private ?float $destinationLatitude = null,
        private ?float $destinationLongitude = null,
    ) {
        $this->events = $events;
    }

    public function id(): TripCalculationId
    {
        return $this->id;
    }

    public function tripCalculationLinkId(): ?TripCalculationLinkId
    {
        return $this->tripCalculationLinkId;
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

    public function destinationPlaceReference(): ?string
    {
        return $this->destinationPlaceReference;
    }

    public function destinationLabel(): ?string
    {
        return $this->destinationLabel;
    }

    public function destinationLatitude(): ?float
    {
        return $this->destinationLatitude;
    }

    public function destinationLongitude(): ?float
    {
        return $this->destinationLongitude;
    }

    public function roundTrip(): bool
    {
        return $this->roundTrip;
    }

    public function trips(): ?int
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

    public function overnightStays(): int
    {
        return ($this->nights ?? 0) * ($this->people ?? 1);
    }

    public function overnightStaysPerPerson(): float
    {
        return $this->overnightStays() / max(1, $this->people ?? 1);
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

    /** @return list<TripCalculationLeg> */
    public function legs(): array
    {
        $legs = $this->legs instanceof Collection ? $this->legs->toArray() : (array) $this->legs;
        $legs = array_values($legs);

        usort($legs, static fn(TripCalculationLeg $a, TripCalculationLeg $b): int => $a->position() <=> $b->position());

        return $legs;
    }

    /** @param list<ResolvedLeg> $legs */
    public function register(
        TripCalculationLinkId $tripCalculationLinkId,
        ?TripWaypoint $origin,
        ?TripWaypoint $destination,
        bool $roundTrip,
        int $trips,
        ?int $nights,
        ?int $people,
        array $legs,
        Emissions $mobilityEmissions,
        Emissions $overnightEmissions,
        ?EmissionFactorGroupId $emissionFactorGroupId,
        int $calculationYear,
        ?\DateTimeImmutable $registeredAt = null,
    ): void {
        $this->recordApplyAndPublish(
            new TripCalculationRegistered(
                tripCalculationId: $this->id(),
                tripCalculationLinkId: $tripCalculationLinkId,
                origin: $origin,
                destination: $destination,
                roundTrip: $roundTrip,
                trips: $trips,
                nights: $nights,
                people: $people,
                legs: $legs,
                mobilityEmissions: $mobilityEmissions,
                overnightEmissions: $overnightEmissions,
                emissionFactorGroupId: $emissionFactorGroupId,
                calculationYear: $calculationYear,
                occurredOn: $registeredAt,
            ),
        );
    }

    public function applyTripCalculationRegistered(TripCalculationRegistered $event): void
    {
        $this->tripCalculationLinkId = $event->tripCalculationLinkId();

        $origin = $event->origin();
        $this->originPlaceReference = $origin?->reference;
        $this->originLabel = $origin?->label;
        $this->originLatitude = $origin?->point?->latitude;
        $this->originLongitude = $origin?->point?->longitude;

        $destination = $event->destination();
        $this->destinationPlaceReference = $destination?->reference;
        $this->destinationLabel = $destination?->label;
        $this->destinationLatitude = $destination?->point?->latitude;
        $this->destinationLongitude = $destination?->point?->longitude;

        $this->roundTrip = $event->roundTrip();
        $this->trips = $event->trips();
        $this->nights = $event->nights();
        $this->people = $event->people();

        $this->mobilityEmissions = $event->mobilityEmissions();
        $this->overnightEmissions = $event->overnightEmissions();
        $this->emissions = $event->mobilityEmissions()->add($event->overnightEmissions());

        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->calculationYear = $event->calculationYear();
        $this->registeredAt = $event->occurredOn();

        $this->legs = array_map(
            fn(ResolvedLeg $leg): TripCalculationLeg => new TripCalculationLeg(
                id: TripCalculationLegId::generate(),
                tripCalculation: $this,
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
        $this->recordApplyAndPublish(new TripCalculationConsentGranted($this->id(), $consentProof));
    }

    public function applyTripCalculationConsentGranted(TripCalculationConsentGranted $event): void
    {
        $this->consentProof = $event->consentProof();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
