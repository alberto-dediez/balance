<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Complete;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CompletePreventionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof CompletePreventionPlanRequest;
    }

    public function __invoke(CompletePreventionPlanRequest $request, User $user): ?CommandResponse
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (null === $preventionPlan) {
            throw new \InvalidArgumentException('Prevention plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($preventionPlan, SecurityAction::Edit, $user);

        $preventionPlan->markAsCompleted();

        $this->preventionPlanRepository->add($preventionPlan);

        return null;
    }
}
