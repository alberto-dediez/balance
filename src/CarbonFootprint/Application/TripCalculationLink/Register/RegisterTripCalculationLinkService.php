<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\Register;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterTripCalculationLinkService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private PropertyRepository $propertyRepository,
        private PropertyAuthorizer $propertyAuthorizer,
    ) {}

    public function __invoke(RegisterTripCalculationLinkRequest $request, User $user): RegisterTripCalculationLinkResponse
    {
        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $this->propertyAuthorizer->denyUnlessCanDoThat($property, Action::Edit, $user);

        $existing = $this->tripCalculationLinkRepository->findByPropertyId($request->propertyId);

        if (null !== $existing) {
            return new RegisterTripCalculationLinkResponse($existing);
        }

        $accountId = $property->accountId();

        if (null == $accountId) {
            throw new \InvalidArgumentException('Property has no accountId');
        }

        $tripCalculationLink = new TripCalculationLink(TripCalculationLinkId::generate());
        $tripCalculationLink->register(
            propertyId: $request->propertyId,
            accountId: $accountId,
            token: TripCalculationLink::generateToken(),
        );

        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        return new RegisterTripCalculationLinkResponse($tripCalculationLink);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterTripCalculationLinkRequest;
    }
}
