<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;

final class RegisterAttendeeSurveyResponseService
{
    public function __construct(
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private MobilityRepository $mobilityRepository,
    ) {}

    public function __invoke(RegisterAttendeeSurveyResponseRequest $request): RegisterAttendeeSurveyResponseResponse
    {
        $attendeeSurvey = $this->attendeeSurveyRepository->findByToken($request->token);

        if (null === $attendeeSurvey) {
            throw new AttendeeSurveyTokenInvalidException();
        }

        if ($attendeeSurvey->status()->isClosed()) {
            throw new AttendeeSurveyClosedException();
        }

        $intercityMobilityId = $this->resolveMobilityId($request->intercityMobilityId);
        $cityMobilityId = $this->resolveMobilityId($request->cityMobilityId);

        $hasIntercityData = $request->cameFromAnotherCity && null !== $intercityMobilityId;
        $hasCityData = null !== $cityMobilityId;

        if (!$hasIntercityData && !$hasCityData) {
            throw new \InvalidArgumentException('At least one of the intercity or city blocks must contain data.');
        }

        if ($hasIntercityData) {
            if (null === $request->intercityDays || $request->intercityDays < 1) {
                throw new \InvalidArgumentException('Intercity days must be at least 1.');
            }

            if (null === $request->intercityVehicleOccupants || $request->intercityVehicleOccupants < 1) {
                throw new \InvalidArgumentException('Intercity vehicle occupants must be at least 1.');
            }

            if (null === $request->intercityDistanceKm || $request->intercityDistanceKm <= 0) {
                throw new \InvalidArgumentException('Intercity distance must be greater than 0.');
            }
        }

        if ($request->usedHotel && (null === $request->hotelNights || $request->hotelNights < 1)) {
            throw new \InvalidArgumentException('Hotel nights must be at least 1 when a hotel was used.');
        }

        if ($hasCityData) {
            if (null === $request->cityDays || $request->cityDays < 1) {
                throw new \InvalidArgumentException('City days must be at least 1.');
            }

            if (null === $request->cityVehicleOccupants || $request->cityVehicleOccupants < 1) {
                throw new \InvalidArgumentException('City vehicle occupants must be at least 1.');
            }

            if (null === $request->cityDistanceKm || $request->cityDistanceKm <= 0) {
                throw new \InvalidArgumentException('City distance must be greater than 0.');
            }
        }

        $attendeeSurveyResponse = new AttendeeSurveyResponse(id: AttendeeSurveyResponseId::generate());
        $attendeeSurveyResponse->register(
            attendeeSurveyId: $attendeeSurvey->id(),
            cameFromAnotherCity: $hasIntercityData,
            intercityDays: $hasIntercityData ? $request->intercityDays : null,
            intercityMobilityId: $hasIntercityData ? $intercityMobilityId : null,
            intercityVehicleOccupants: $hasIntercityData ? $request->intercityVehicleOccupants : null,
            intercityDistanceKm: $hasIntercityData ? $request->intercityDistanceKm : null,
            usedHotel: $request->usedHotel,
            hotelNights: $request->usedHotel ? $request->hotelNights : null,
            cityDays: $hasCityData ? $request->cityDays : null,
            cityMobilityId: $hasCityData ? $cityMobilityId : null,
            cityVehicleOccupants: $hasCityData ? $request->cityVehicleOccupants : null,
            cityDistanceKm: $hasCityData ? $request->cityDistanceKm : null,
        );

        $this->attendeeSurveyResponseRepository->add($attendeeSurveyResponse);

        return new RegisterAttendeeSurveyResponseResponse($attendeeSurveyResponse->id());
    }

    private function resolveMobilityId(?string $mobilityId): ?MobilityId
    {
        if (null === $mobilityId || '' === $mobilityId) {
            return null;
        }

        $id = new MobilityId($mobilityId);

        if (null === $this->mobilityRepository->find($id)) {
            throw new \InvalidArgumentException(sprintf('Mobility "%s" does not exist.', $mobilityId));
        }

        return $id;
    }
}
