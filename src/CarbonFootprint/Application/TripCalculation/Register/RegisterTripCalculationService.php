<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Register;

use App\CarbonFootprint\Application\Trip\LegInput;
use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripLegChoice;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsResolver;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class RegisterTripCalculationService
{
    public function __construct(
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationRepository $tripCalculationRepository,
        private MobilityRepository $mobilityRepository,
        private PropertyRepository $propertyRepository,
        private PropertyLocator $propertyLocator,
        private PlaceFinder $placeFinder,
        private TripLegsResolver $tripLegsResolver,
        private TripLegsAccumulator $tripLegsAccumulator,
        private TripEmissionsCalculator $tripEmissionsCalculator,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(RegisterTripCalculationRequest $request): RegisterTripCalculationResponse
    {
        $tripCalculationLink = $this->tripCalculationLinkRepository->findByToken($request->token);

        if (null === $tripCalculationLink) {
            throw new TripCalculationLinkTokenInvalidException();
        }

        $scope = $tripCalculationLink->scope();

        $originPlaceReference = $scope->includesTransport() ? $request->originPlaceReference : null;
        $legInputs = $scope->includesTransport() ? $request->legs : [];
        $roundTrip = $scope->includesTransport() && $request->roundTrip;
        $trips = $scope->includesTransport() ? $request->trips : 1;
        $nights = $scope->includesOvernightStay() ? $request->nights : 0;

        if (null === $trips || $trips < 1) {
            throw new \InvalidArgumentException('The number of trips must be at least 1.');
        }

        if (null !== $nights && $nights < 0) {
            throw new \InvalidArgumentException('The number of nights cannot be negative.');
        }

        if (TripCalculationScope::Transport === $scope && [] === $legInputs) {
            throw new \InvalidArgumentException('Debes indicar cómo llegaste.');
        }

        if (TripCalculationScope::OvernightStay === $scope && (null === $nights || $nights < 1)) {
            throw new \InvalidArgumentException('Debes indicar cuántas noches.');
        }

        $propertyId = $tripCalculationLink->propertyId();
        $property = null !== $propertyId ? $this->propertyRepository->find($propertyId) : null;

        if (null === $property) {
            throw new TripCalculationLinkTokenInvalidException();
        }

        $origin = [] !== $legInputs ? $this->waypoint($originPlaceReference) : null;
        $legs = [];
        $kmByMobilityId = [];

        if ([] !== $legInputs) {
            $destination = new TripWaypoint(label: $property->name(), point: $this->propertyLocator->locate($property));
            $legs = $this->resolveLegs($origin ?? new TripWaypoint(), $destination, $legInputs);

            $this->tripLegsAccumulator->accumulate($kmByMobilityId, $legs, $trips, $roundTrip);
        }

        $emissionFactorGroup = $this->emissionFactorGroupRepository->findDefault();

        if (null === $emissionFactorGroup) {
            throw new \InvalidArgumentException('There is no default emission factor group configured.');
        }

        $calculationYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $tripEmissions = $this->tripEmissionsCalculator->calculate(
            kmByMobilityId: $kmByMobilityId,
            nights: (float) ($nights ?? 0),
            emissionFactorGroupId: $emissionFactorGroup->id(),
            year: $calculationYear,
        );

        $tripCalculation = new TripCalculation(id: TripCalculationId::generate());
        $tripCalculation->register(
            tripCalculationLinkId: $tripCalculationLink->id(),
            origin: $origin,
            roundTrip: $roundTrip,
            trips: $trips,
            nights: $nights,
            legs: $legs,
            mobilityEmissions: $tripEmissions->mobility,
            overnightEmissions: $tripEmissions->overnight,
            emissionFactorGroupId: $emissionFactorGroup->id(),
            calculationYear: $calculationYear,
        );

        $this->tripCalculationRepository->add($tripCalculation);

        return new RegisterTripCalculationResponse(
            tripCalculationId: $tripCalculation->id(),
            emissions: $tripCalculation->emissions(),
            mobilityEmissions: $tripEmissions->mobility,
            overnightEmissions: $tripEmissions->overnight,
            overnightStayCatalogMissing: $tripEmissions->overnightStayCatalogMissing,
        );
    }

    /**
     * @param list<LegInput> $legInputs
     *
     * @return list<ResolvedLeg>
     */
    private function resolveLegs(TripWaypoint $origin, TripWaypoint $destination, array $legInputs): array
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

        $legs = $this->tripLegsResolver->resolve(LegKind::Intercity, $waypoints, $choices);

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
