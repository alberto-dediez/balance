<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\List;

use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListSurplusCausesService
{
    public function __construct(private readonly SurplusCauseRepository $repository, private readonly SurplusCauseAuthorizer $authorizer) {}

    public function __invoke(ListSurplusCausesRequest $request, User $user): ListSurplusCausesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new SurplusCause(id: SurplusCauseId::generate()), Action::List, $user);

        $surplusCauses = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListSurplusCausesResponse($surplusCauses, $totalResults);
    }
}
