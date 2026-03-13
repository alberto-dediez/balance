<?php

namespace App\FoodLoss\Application\Action\List;

use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Domain\Model\Action\ActionRepository;

class ListActionsService
{
    public function __construct(private ActionRepository $repository, private ActionAuthorizer $authorizer)
    {

    }

    public function __invoke(ListActionsRequest $request, User $user): ListActionsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Action(id: ActionId::generate()), SecurityAction::List, $user);

        $actions = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListActionsResponse($actions, $totalResults);
    }
}