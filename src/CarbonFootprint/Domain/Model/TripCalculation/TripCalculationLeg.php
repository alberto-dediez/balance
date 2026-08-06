<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLeg;

class TripCalculationLeg implements TripLeg
{
    public function __construct(
        private TripCalculationLegId $id,
        private TripCalculation $tripCalculation,
        private int $position,
        private LegKind $kind,
        private MobilityId $mobilityId,
        private ?int $vehicleOccupants = null,
        private ?string $originPlaceReference = null,
        private ?string $originLabel = null,
        private ?float $originLatitude = null,
        private ?float $originLongitude = null,
        private ?string $destinationPlaceReference = null,
        private ?string $destinationLabel = null,
        private ?float $destinationLatitude = null,
        private ?float $destinationLongitude = null,
        private ?float $distanceKm = null,
        private DistanceSource $distanceSource = DistanceSource::Manual,
    ) {}

    public function id(): TripCalculationLegId
    {
        return $this->id;
    }

    public function tripCalculation(): TripCalculation
    {
        return $this->tripCalculation;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function kind(): LegKind
    {
        return $this->kind;
    }

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
    }

    public function vehicleOccupants(): ?int
    {
        return $this->vehicleOccupants;
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

    /** One way distance of this leg. */
    public function distanceKm(): ?float
    {
        return $this->distanceKm;
    }

    public function distanceSource(): DistanceSource
    {
        return $this->distanceSource;
    }
}
