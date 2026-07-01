<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Remove;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveActivityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveActivityRequest $request, User $user): RemoveActivityResponse
    {
        $activity = $this->activityRepository->findById($request->activityId);

        if (null === $activity) {
            return new RemoveActivityResponse(activityId: $request->activityId);
        }

        $this->authorizer->denyUnlessCanDoThat($activity, SecurityAction::Remove, $user);

        $activity->remove();

        $this->activityRepository->remove($activity);

        return new RemoveActivityResponse(activityId: $request->activityId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveActivityRequest;
    }
}
