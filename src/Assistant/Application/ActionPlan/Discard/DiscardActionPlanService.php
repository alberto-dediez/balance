<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Discard;

use App\Assistant\Application\ActionPlan\ActionPlanAuthorizer;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class DiscardActionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActionPlanRepository $repository,
        private ActionPlanAuthorizer $authorizer,
        private Clock $clock,
    ) {}

    public function __invoke(DiscardActionPlanRequest $request, User $user): DiscardActionPlanResponse
    {
        $plan = $this->repository->find($request->planId);

        if (null === $plan) {
            throw new \InvalidArgumentException('Plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($plan, Action::Edit, $user);

        $plan->discard($request->reason, $this->clock->now());

        $this->repository->add($plan);

        return new DiscardActionPlanResponse($plan->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DiscardActionPlanRequest;
    }
}
