<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\List;

use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListTrainingSessionsService
{
    public function __construct(private TrainingSessionRepository $repository, private TrainingSessionAuthorizer $authorizer) {}

    public function __invoke(ListTrainingSessionsRequest $request, User $user): ListTrainingSessionsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new TrainingSession(id: TrainingSessionId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $trainingSessions = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListTrainingSessionsResponse($trainingSessions, $totalResults);
    }
}
