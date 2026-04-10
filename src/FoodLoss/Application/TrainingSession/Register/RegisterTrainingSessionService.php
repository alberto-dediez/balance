<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Register;

use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterTrainingSessionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TrainingSessionRepository $trainingSessionRepository,
        private TrainingSessionAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterTrainingSessionRequest $request, User $user): RegisterTrainingSessionResponse
    {
        $id = TrainingSessionId::generate();

        assert($request->accountId instanceof AccountId);

        $this->authorizer->denyUnlessCanDoThat(
            new TrainingSession(id: $id, accountId: $request->accountId),
            SecurityAction::Register,
            $user,
        );

        $trainingSession = new TrainingSession($id);
        $trainingSession->register(
            sessionDate: $request->sessionDate,
            title: $request->title,
            topic: $request->topic,
            accountId: $request->accountId,
        );

        $this->trainingSessionRepository->add($trainingSession);

        return new RegisterTrainingSessionResponse(
            trainingSessionId: $trainingSession->id(),
            title: $trainingSession->title(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterTrainingSessionRequest;
    }
}
