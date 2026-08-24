<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Application\Trip\LegInput;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripLegChoice;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsResolver;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Shared\Domain\Model\Consent\ConsentProof;
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
        private TripLegsResolver $tripLegsResolver,
        private TripLegsAccumulator $tripLegsAccumulator,
        private TripEmissionsCalculator $tripEmissionsCalculator,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private string $consentPolicyVersion,
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

        $kmByMobilityId = [];
        $this->tripLegsAccumulator->accumulate($kmByMobilityId, $intercityLegs, $intercityTrips, $cameFromAnotherCity && $request->intercityRoundTrip);
        $this->tripLegsAccumulator->accumulate($kmByMobilityId, $cityLegs, $cityTrips, $movedWithinCity && $request->cityRoundTrip);

        [$emissionFactorGroupId, $calculationYear] = $this->calculationContext($eventId);

        $mobilityEmissions = new Emissions();
        $overnightEmissions = new Emissions();
        $overnightStayCatalogMissing = false;

        if (null !== $emissionFactorGroupId && null !== $calculationYear) {
            try {
                $tripEmissions = $this->tripEmissionsCalculator->calculate(
                    kmByMobilityId: $kmByMobilityId,
                    nights: (float) ($usedHotel ? $request->hotelNights ?? 0 : 0),
                    emissionFactorGroupId: $emissionFactorGroupId,
                    year: $calculationYear,
                );

                $mobilityEmissions = $tripEmissions->mobility;
                $overnightEmissions = $tripEmissions->overnight;
                $overnightStayCatalogMissing = $tripEmissions->overnightStayCatalogMissing;
            } catch (\InvalidArgumentException) {
                $emissionFactorGroupId = null;
                $calculationYear = null;
            }
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
            mobilityEmissions: $mobilityEmissions,
            overnightEmissions: $overnightEmissions,
            emissionFactorGroupId: $emissionFactorGroupId,
            calculationYear: $calculationYear,
        );

        $attendeeSurveyResponse->grantConsent(new ConsentProof(
            consentedAt: new \DateTimeImmutable('NOW'),
            policyVersion: $this->consentPolicyVersion,
            locale: LocaleGetter::locale()->value,
            ip: $request->consentIp,
            userAgent: $request->consentUserAgent,
            acceptLanguage: $request->consentAcceptLanguage,
        ));

        $this->attendeeSurveyResponseRepository->add($attendeeSurveyResponse);

        return new RegisterAttendeeSurveyResponseResponse(
            attendeeSurveyResponseId: $attendeeSurveyResponse->id(),
            emissions: $attendeeSurveyResponse->emissions(),
            mobilityEmissions: $mobilityEmissions,
            overnightEmissions: $overnightEmissions,
            overnightStayCatalogMissing: $overnightStayCatalogMissing,
        );
    }

    /** @return array{0: ?EmissionFactorGroupId, 1: ?int} */
    private function calculationContext(?EventId $eventId): array
    {
        $eventCarbonFootprint = null !== $eventId ? $this->eventCarbonFootprintRepository->findByEventId($eventId) : null;
        $emissionFactorGroupId = $eventCarbonFootprint?->emissionFactorGroupId();

        if (null !== $emissionFactorGroupId) {
            return [$emissionFactorGroupId, $eventCarbonFootprint->calculationYear()];
        }

        $emissionFactorGroup = $this->emissionFactorGroupRepository->findDefault();

        if (null === $emissionFactorGroup) {
            return [null, null];
        }

        return [$emissionFactorGroup->id(), (int) (new \DateTimeImmutable('NOW'))->format('Y')];
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
