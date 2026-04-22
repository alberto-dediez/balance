<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateActions;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdatePreventionPlanActionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdatePreventionPlanActionsRequest $request, User $user): UpdatePreventionPlanActionsResponse
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (null === $preventionPlan) {
            throw new \InvalidArgumentException('Prevention plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($preventionPlan, SecurityAction::Edit, $user);

        $version = $preventionPlan->versions()->findByVersionNumber($preventionPlan->currentVersion());
        $currentActions = $version?->proposedActions()->toArray() ?? [];

        foreach ($currentActions as $current) {
            $matchFound = false;
            foreach ($request->proposedActions as $data) {
                if (null === $data->propertyId || null === $data->actionId || null === $data->criticalPointId) {
                    continue;
                }
                if ($current->propertyId()->equals($data->propertyId)
                    && $current->actionId()->equals($data->actionId)
                    && $current->criticalPointId()->equals($data->criticalPointId)) {
                    $matchFound = true;
                    break;
                }
            }
            if (!$matchFound) {
                $preventionPlan->removeProposedAction($current->propertyId(), $current->actionId(), $current->criticalPointId());
            }
        }

        foreach ($request->proposedActions as $data) {
            if (null === $data->propertyId || null === $data->actionId || null === $data->criticalPointId) {
                continue;
            }
            $preventionPlan->addProposedAction($data->propertyId, $data->actionId, $data->criticalPointId);
        }

        $preventionPlan->markAsConfigured();
        $this->preventionPlanRepository->add($preventionPlan);

        return new UpdatePreventionPlanActionsResponse(
            preventionPlanId: $preventionPlan->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePreventionPlanActionsRequest;
    }
}
