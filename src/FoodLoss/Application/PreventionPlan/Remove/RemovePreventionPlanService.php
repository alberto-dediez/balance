<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Remove;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemovePreventionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $repository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function __invoke(RemovePreventionPlanRequest $request, User $user): RemovePreventionPlanResponse
    {
        $preventionPlan = $this->repository->find($request->preventionPlanId);

        if (null == $preventionPlan) {
            throw new \InvalidArgumentException('Prevention plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($preventionPlan, SecurityAction::Remove, $user);

        $preventionPlan->remove();

        $this->repository->add($preventionPlan);

        return new RemovePreventionPlanResponse(
            preventionPlanId: $preventionPlan->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemovePreventionPlanRequest;
    }
}
