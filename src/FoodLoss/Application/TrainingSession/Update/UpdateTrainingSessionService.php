<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Update;

use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateTrainingSessionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TrainingSessionRepository $trainingSessionRepository,
        private TrainingSessionAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateTrainingSessionRequest $request, User $user): UpdateTrainingSessionResponse
    {
        $trainingSession = $this->trainingSessionRepository->find($request->trainingSessionId);

        if (null == $trainingSession) {
            throw new \InvalidArgumentException('TrainingSession not found');
        }

        $this->authorizer->denyUnlessCanDoThat($trainingSession, SecurityAction::Edit, $user);

        $trainingSession->update(
            sessionDate: $request->sessionDate,
            title: $request->title,
            topic: $request->topic,
        );

        $this->trainingSessionRepository->add($trainingSession);

        return new UpdateTrainingSessionResponse(
            trainingSessionId: $trainingSession->id(),
            title: $trainingSession->title(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateTrainingSessionRequest;
    }
}
