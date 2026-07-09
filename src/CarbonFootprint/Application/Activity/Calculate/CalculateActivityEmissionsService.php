<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Calculate;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CalculateActivityEmissionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private ActivityEmissionsResolver $emissionsResolver,
    ) {}

    public function __invoke(CalculateActivityEmissionsRequest $request, User $user): CalculateActivityEmissionsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        return $this->emissionsResolver->resolve(
            $eventCarbonFootprint,
            $request->activityType,
            $request->itemId,
            $request->condition,
            $request->quantity,
            $request->secondaryItemId,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CalculateActivityEmissionsRequest;
    }
}
