<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Calculate;

use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
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
        private EmissionFactorSelectorRegistry $selectorRegistry,
        private EmissionsCalculator $calculator,
    ) {}

    public function __invoke(CalculateActivityEmissionsRequest $request, User $user): CalculateActivityEmissionsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId()
            ?? throw new \InvalidArgumentException(sprintf('EventCarbonFootprint "%s" has no emission factor group configured.', $eventCarbonFootprint->id()));

        $emissionFactor = $this->selectorRegistry
            ->forActivityType($request->activityType)
            ->select($request->itemId, $eventCarbonFootprint->calculationYear(), $request->condition, $emissionFactorGroupId, $request->secondaryItemId);

        return new CalculateActivityEmissionsResponse(
            emissionFactor: $emissionFactor,
            emissions: $this->calculator->calculate($emissionFactor, $request->quantity),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CalculateActivityEmissionsRequest;
    }
}
