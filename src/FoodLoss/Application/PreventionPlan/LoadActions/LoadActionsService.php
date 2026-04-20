<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\LoadActions;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionsLoader;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LoadActionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private ProposedActionsLoader $proposedActionsLoader,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof LoadActionsRequest;
    }

    public function __invoke(LoadActionsRequest $request, User $user): null
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (!$preventionPlan instanceof PreventionPlan) {
            return null;
        }

        $version = $preventionPlan->currentPlanVersion();
        $existingActions = $version->proposedActions()->toArray();

        $propertiesWithActions = [];
        foreach ($existingActions as $action) {
            $propertiesWithActions[(string) $action->propertyId()] = true;
        }

        $propertyIdsWithoutActions = array_values(array_filter(
            $preventionPlan->propertyIds(),
            fn(PropertyId $id) => !isset($propertiesWithActions[(string) $id]),
        ));

        foreach ($propertyIdsWithoutActions as $propertyId) {
            $suggestions = $this->proposedActionsLoader->suggestFor($propertyId);

            foreach ($suggestions as $suggestion) {
                $preventionPlan->addProposedAction($propertyId, $suggestion->actionId, $suggestion->criticalPointId);
            }
        }

        $preventionPlan->markCurrentVersionAsLoaded();
        $this->preventionPlanRepository->add($preventionPlan);

        return null;
    }
}
