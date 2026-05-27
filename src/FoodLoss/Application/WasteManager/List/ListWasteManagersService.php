<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\List;

use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListWasteManagersService
{
    public function __construct(private WasteManagerRepository $repository, private WasteManagerAuthorizer $authorizer) {}

    public function __invoke(ListWasteManagersRequest $request, User $user): ListWasteManagersResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new WasteManager(id: WasteManagerId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $wasteManagers = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListWasteManagersResponse($wasteManagers, $totalResults);
    }
}
