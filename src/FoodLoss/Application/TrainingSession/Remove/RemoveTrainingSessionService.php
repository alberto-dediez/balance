<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Remove;

use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveTrainingSessionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private TrainingSessionRepository $repository, private TrainingSessionAuthorizer $authorizer) {}

    public function __invoke(RemoveTrainingSessionRequest $request, User $user): RemoveTrainingSessionResponse
    {
        $trainingSession = $this->repository->find($request->trainingSessionId);

        if (null == $trainingSession) {
            throw new \InvalidArgumentException('TrainingSession not found');
        }

        $this->authorizer->denyUnlessCanDoThat($trainingSession, SecurityAction::Remove, $user);

        $trainingSession->remove();

        $this->repository->add($trainingSession);

        return new RemoveTrainingSessionResponse(
            trainingSessionId: $trainingSession->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveTrainingSessionRequest;
    }
}
