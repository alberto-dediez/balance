<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\UpdateScope;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateTripCalculationLinkScopeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationLinkAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateTripCalculationLinkScopeRequest $request, User $user): UpdateTripCalculationLinkScopeResponse
    {
        $tripCalculationLink = $this->tripCalculationLinkRepository->find($request->tripCalculationLinkId);

        if (null == $tripCalculationLink) {
            throw new \InvalidArgumentException('TripCalculationLink not found');
        }

        $this->authorizer->denyUnlessCanDoThat($tripCalculationLink, Action::Edit, $user);

        $tripCalculationLink->updateScope($request->scope);

        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        return new UpdateTripCalculationLinkScopeResponse($tripCalculationLink);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateTripCalculationLinkScopeRequest;
    }
}
