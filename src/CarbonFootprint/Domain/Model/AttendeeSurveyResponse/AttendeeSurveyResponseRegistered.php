<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AttendeeSurveyResponseRegistered extends DomainEvent
{
    protected static string $eventName = 'attendeeSurveyResponseRegistered';

    public function __construct(
        private AttendeeSurveyResponseId $attendeeSurveyResponseId,
        private AttendeeSurveyId $attendeeSurveyId,
        private bool $cameFromAnotherCity,
        private ?int $intercityDays,
        private ?MobilityId $intercityMobilityId,
        private ?int $intercityVehicleOccupants,
        private ?float $intercityDistanceKm,
        private bool $usedHotel,
        private ?int $hotelNights,
        private ?int $cityDays,
        private ?MobilityId $cityMobilityId,
        private ?int $cityVehicleOccupants,
        private ?float $cityDistanceKm,
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
            'intercityDays' => $this->intercityDays,
            'intercityMobilityId' => null !== $this->intercityMobilityId ? (string) $this->intercityMobilityId : null,
            'intercityVehicleOccupants' => $this->intercityVehicleOccupants,
            'intercityDistanceKm' => $this->intercityDistanceKm,
            'usedHotel' => $this->usedHotel,
            'hotelNights' => $this->hotelNights,
            'cityDays' => $this->cityDays,
            'cityMobilityId' => null !== $this->cityMobilityId ? (string) $this->cityMobilityId : null,
            'cityVehicleOccupants' => $this->cityVehicleOccupants,
            'cityDistanceKm' => $this->cityDistanceKm,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            attendeeSurveyResponseId: new AttendeeSurveyResponseId($payload['attendeeSurveyResponseId']),
            attendeeSurveyId: new AttendeeSurveyId($payload['attendeeSurveyId']),
            cameFromAnotherCity: $payload['cameFromAnotherCity'],
            intercityDays: $payload['intercityDays'] ?? null,
            intercityMobilityId: null !== ($payload['intercityMobilityId'] ?? null) ? new MobilityId($payload['intercityMobilityId']) : null,
            intercityVehicleOccupants: $payload['intercityVehicleOccupants'] ?? null,
            intercityDistanceKm: $payload['intercityDistanceKm'] ?? null,
            usedHotel: $payload['usedHotel'],
            hotelNights: $payload['hotelNights'] ?? null,
            cityDays: $payload['cityDays'] ?? null,
            cityMobilityId: null !== ($payload['cityMobilityId'] ?? null) ? new MobilityId($payload['cityMobilityId']) : null,
            cityVehicleOccupants: $payload['cityVehicleOccupants'] ?? null,
            cityDistanceKm: $payload['cityDistanceKm'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
