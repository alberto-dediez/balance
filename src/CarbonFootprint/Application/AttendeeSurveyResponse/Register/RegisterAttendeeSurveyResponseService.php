<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeTripLegsResolver;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\LegKind;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\TripLegChoice;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\TripWaypoint;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class RegisterAttendeeSurveyResponseService
{
    public function __construct(
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private MobilityRepository $mobilityRepository,
        private EventRepository $eventRepository,
        private EventVenueLocator $eventVenueLocator,
        private PlaceFinder $placeFinder,
        private AttendeeTripLegsResolver $tripLegsResolver,
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

        $eventId = $attendeeSurvey->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;
        $venue = $this->eventVenueLocator->locate($event);

        $cameFromAnotherCity = $request->cameFromAnotherCity && [] !== $request->intercityLegs;
        $movedWithinCity = $request->movedWithinCity && [] !== $request->cityLegs;

        if (!$cameFromAnotherCity && !$movedWithinCity) {
            throw new \InvalidArgumentException('At least one of the intercity or city blocks must contain data.');
        }

        $usedHotel = $cameFromAnotherCity && $request->usedHotel;

        if ($usedHotel && (null === $request->hotelNights || $request->hotelNights < 1)) {
            throw new \InvalidArgumentException('Hotel nights must be at least 1 when a hotel was used.');
        }

        $origin = $this->waypoint($request->originPlaceReference);
        $accommodation = $usedHotel ? $this->waypoint($request->accommodationPlaceReference) : null;
        $cityOrigin = $this->waypoint($request->cityOriginPlaceReference) ?? $accommodation;
        $venueWaypoint = new TripWaypoint(label: $event?->venueLabel(), point: $venue);

        $intercityTrips = null;
        $intercityLegs = [];

        if ($cameFromAnotherCity) {
            $intercityTrips = $usedHotel ? 1 : $this->trips($request->intercityTrips, 'intercity');

            $intercityLegs = $this->resolveLegs(
                LegKind::Intercity,
                $origin ?? new TripWaypoint(),
                $usedHotel && null !== $accommodation ? $accommodation : $venueWaypoint,
                $request->intercityLegs,
            );
        }

        $cityTrips = null;
        $cityLegs = [];

        if ($movedWithinCity) {
            $cityTrips = $this->trips($request->cityTrips, 'city');

            $cityLegs = $this->resolveLegs(
                LegKind::City,
                $cityOrigin ?? new TripWaypoint(),
                $venueWaypoint,
                $request->cityLegs,
            );
        }

        $attendeeSurveyResponse = new AttendeeSurveyResponse(id: AttendeeSurveyResponseId::generate());
        $attendeeSurveyResponse->register(
            attendeeSurveyId: $attendeeSurvey->id(),
            cameFromAnotherCity: $cameFromAnotherCity,
            origin: $cameFromAnotherCity ? $origin : null,
            usedHotel: $usedHotel,
            accommodation: $accommodation,
            hotelNights: $usedHotel ? $request->hotelNights : null,
            intercityRoundTrip: $cameFromAnotherCity && $request->intercityRoundTrip,
            intercityTrips: $intercityTrips,
            movedWithinCity: $movedWithinCity,
            cityOrigin: $movedWithinCity ? $cityOrigin : null,
            cityRoundTrip: $movedWithinCity && $request->cityRoundTrip,
            cityTrips: $cityTrips,
            legs: array_merge($intercityLegs, $cityLegs),
        );

        $this->attendeeSurveyResponseRepository->add($attendeeSurveyResponse);

        return new RegisterAttendeeSurveyResponseResponse($attendeeSurveyResponse->id());
    }

    /**
     * @param list<LegInput> $legInputs
     *
     * @return list<ResolvedLeg>
     */
    private function resolveLegs(LegKind $kind, TripWaypoint $origin, TripWaypoint $destination, array $legInputs): array
    {
        $waypoints = [$origin];
        $choices = [];

        foreach ($legInputs as $index => $legInput) {
            $mobility = $this->resolveMobility($legInput->mobilityId);

            if (null !== $legInput->vehicleOccupants && $legInput->vehicleOccupants < 1) {
                throw new \InvalidArgumentException('Vehicle occupants must be at least 1.');
            }

            $choices[] = new TripLegChoice(
                mobilityId: $mobility->id(),
                travelMode: $mobility->travelMode(),
                vehicleOccupants: $legInput->vehicleOccupants,
                manualDistanceKm: $legInput->manualDistanceKm,
            );

            if ($index < count($legInputs) - 1) {
                $waypoints[] = $this->waypoint($legInput->destinationPlaceReference) ?? new TripWaypoint();
            }
        }

        $waypoints[] = $destination;

        $legs = $this->tripLegsResolver->resolve($kind, $waypoints, $choices);

        foreach ($legs as $leg) {
            if (null === $leg->distanceKm) {
                throw new \InvalidArgumentException('The distance of a leg could not be calculated. Please enter its kilometres manually.');
            }
        }

        return $legs;
    }

    private function waypoint(?string $placeReference): ?TripWaypoint
    {
        $placeReference = null !== $placeReference ? trim($placeReference) : '';

        if ('' === $placeReference) {
            return null;
        }

        $place = $this->placeFinder->resolve($placeReference, LocaleGetter::locale());

        if (null === $place) {
            return new TripWaypoint(reference: $placeReference);
        }

        return new TripWaypoint(reference: $place->reference, label: $place->label, point: $place->point);
    }

    private function trips(?int $trips, string $block): int
    {
        if (null === $trips || $trips < 1) {
            throw new \InvalidArgumentException(sprintf('The number of %s trips must be at least 1.', $block));
        }

        return $trips;
    }

    private function resolveMobility(string $mobilityId): Mobility
    {
        if ('' === $mobilityId) {
            throw new \InvalidArgumentException('A means of transport is required for every leg.');
        }

        $mobility = $this->mobilityRepository->find(new MobilityId($mobilityId));

        if (null === $mobility) {
            throw new \InvalidArgumentException(sprintf('Mobility "%s" does not exist.', $mobilityId));
        }

        return $mobility;
    }
}
