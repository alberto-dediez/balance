<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\List;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListSurplusesService
{
    public function __construct(private SurplusRepository $repository, private SurplusAuthorizer $authorizer) {}

    public function __invoke(ListSurplusesRequest $request, User $user): ListSurplusesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Surplus(id: SurplusId::generate(), accountId: $request->accountId ?? new AccountId('00000000-0000-0000-0000-000000000000')), SecurityAction::List, $user);

        $surpluses = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListSurplusesResponse($surpluses, $totalResults);
    }
}
