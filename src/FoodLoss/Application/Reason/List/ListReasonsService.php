<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\List;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListReasonsService
{
    public function __construct(private ReasonRepository $repository, private ReasonAuthorizer $authorizer) {}

    public function __invoke(ListReasonsRequest $request, User $user): ListReasonsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Reason(id: ReasonId::generate()), SecurityAction::List, $user);

        $reasons = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListReasonsResponse($reasons, $totalResults);
    }
}
