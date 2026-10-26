<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Import;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ImportTripCalculationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TripCalculationRepository $tripCalculationRepository,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationLinkAuthorizer $tripCalculationLinkAuthorizer,
    ) {}

    public function __invoke(ImportTripCalculationRequest $request, User $user): ImportTripCalculationResponse
    {
        $tripCalculationLink = $this->tripCalculationLinkRepository->find($request->tripCalculationLinkId);

        if (null === $tripCalculationLink) {
            throw new \InvalidArgumentException('Trip calculation link not found');
        }

        $this->tripCalculationLinkAuthorizer->denyUnlessCanDoThat($tripCalculationLink, Action::Edit, $user);

        $legs = null !== $request->origin && null !== $request->destination && null !== $request->mobilityId
            ? [
                new ResolvedLeg(
                    position: 0,
                    kind: LegKind::Intercity,
                    mobilityId: $request->mobilityId,
                    vehicleOccupants: $request->vehicleOccupants,
                    origin: $request->origin,
                    destination: $request->destination,
                    distanceKm: $request->distanceKm,
                    distanceSource: DistanceSource::Manual,
                ),
            ]
            : [];

        $tripCalculation = new TripCalculation(TripCalculationId::generate());
        $tripCalculation->register(
            tripCalculationLinkId: $request->tripCalculationLinkId,
            origin: $request->origin,
            destination: $request->destination,
            roundTrip: $request->roundTrip,
            trips: 1,
            nights: $request->nights,
            people: $request->people,
            legs: $legs,
            mobilityEmissions: $request->mobilityEmissions,
            overnightEmissions: $request->overnightEmissions,
            emissionFactorGroupId: null,
            calculationYear: (int) $request->registeredAt->format('Y'),
            registeredAt: $request->registeredAt,
        );

        $this->tripCalculationRepository->add($tripCalculation);

        return new ImportTripCalculationResponse($tripCalculation->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportTripCalculationRequest;
    }
}
