<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\List;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCriticalPointsService
{
    public function __construct(
        private CriticalPointRepository $criticalPointRepository,
        private CriticalPointAuthorizer $authorizer,
    ) {}

    public function __invoke(ListCriticalPointsRequest $request, User $user): ListCriticalPointsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CriticalPoint(id: CriticalPointId::generate()), SecurityAction::List, $user);

        // Force filtering by user's accountId
        $request->accountId = (string) $user->accountId();

        $criticalPoints = $this->criticalPointRepository->findByFilter($request);
        $totalResults = $this->criticalPointRepository->totalResultsByFilter($request);

        return new ListCriticalPointsResponse($criticalPoints, $totalResults);
    }
}
