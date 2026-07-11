<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class AttendeeSurveyResponse implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private AttendeeSurveyResponseId $id,
        private ?AttendeeSurveyId $attendeeSurveyId = null,
        private bool $cameFromAnotherCity = false,
        private ?int $intercityDays = null,
        private ?MobilityId $intercityMobilityId = null,
        private ?int $intercityVehicleOccupants = null,
        private ?float $intercityDistanceKm = null,
        private bool $usedHotel = false,
        private ?int $hotelNights = null,
        private ?int $cityDays = null,
        private ?MobilityId $cityMobilityId = null,
        private ?int $cityVehicleOccupants = null,
        private ?float $cityDistanceKm = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
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

    public function intercityDays(): ?int
    {
        return $this->intercityDays;
    }

    public function intercityMobilityId(): ?MobilityId
    {
        return $this->intercityMobilityId;
    }

    public function intercityVehicleOccupants(): ?int
    {
        return $this->intercityVehicleOccupants;
    }

    public function intercityDistanceKm(): ?float
    {
        return $this->intercityDistanceKm;
    }

    public function usedHotel(): bool
    {
        return $this->usedHotel;
    }

    public function hotelNights(): ?int
    {
        return $this->hotelNights;
    }

    public function cityDays(): ?int
    {
        return $this->cityDays;
    }

    public function cityMobilityId(): ?MobilityId
    {
        return $this->cityMobilityId;
    }

    public function cityVehicleOccupants(): ?int
    {
        return $this->cityVehicleOccupants;
    }

    public function cityDistanceKm(): ?float
    {
        return $this->cityDistanceKm;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function register(
        AttendeeSurveyId $attendeeSurveyId,
        bool $cameFromAnotherCity,
        ?int $intercityDays,
        ?MobilityId $intercityMobilityId,
        ?int $intercityVehicleOccupants,
        ?float $intercityDistanceKm,
        bool $usedHotel,
        ?int $hotelNights,
        ?int $cityDays,
        ?MobilityId $cityMobilityId,
        ?int $cityVehicleOccupants,
        ?float $cityDistanceKm,
    ): void {
        $this->recordApplyAndPublish(
            new AttendeeSurveyResponseRegistered(
                attendeeSurveyResponseId: $this->id(),
                attendeeSurveyId: $attendeeSurveyId,
                cameFromAnotherCity: $cameFromAnotherCity,
                intercityDays: $intercityDays,
                intercityMobilityId: $intercityMobilityId,
                intercityVehicleOccupants: $intercityVehicleOccupants,
                intercityDistanceKm: $intercityDistanceKm,
                usedHotel: $usedHotel,
                hotelNights: $hotelNights,
                cityDays: $cityDays,
                cityMobilityId: $cityMobilityId,
                cityVehicleOccupants: $cityVehicleOccupants,
                cityDistanceKm: $cityDistanceKm,
            ),
        );
    }

    public function applyAttendeeSurveyResponseRegistered(AttendeeSurveyResponseRegistered $event): void
    {
        $this->attendeeSurveyId = $event->attendeeSurveyId();
        $this->cameFromAnotherCity = $event->cameFromAnotherCity();
        $this->intercityDays = $event->intercityDays();
        $this->intercityMobilityId = $event->intercityMobilityId();
        $this->intercityVehicleOccupants = $event->intercityVehicleOccupants();
        $this->intercityDistanceKm = $event->intercityDistanceKm();
        $this->usedHotel = $event->usedHotel();
        $this->hotelNights = $event->hotelNights();
        $this->cityDays = $event->cityDays();
        $this->cityMobilityId = $event->cityMobilityId();
        $this->cityVehicleOccupants = $event->cityVehicleOccupants();
        $this->cityDistanceKm = $event->cityDistanceKm();
        $this->registeredAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
