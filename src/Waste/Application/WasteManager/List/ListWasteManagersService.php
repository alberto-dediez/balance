<?php

namespace App\Waste\Application\WasteManager\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class ListWasteManagersService
{
    public function __construct(private readonly WasteManagerRepository $repository, private readonly WasteManagerAuthorizer $authorizer)
    {

    }

    public function __invoke(ListWasteManagersRequest $request, User $user): ListWasteManagersResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new WasteManager(
            id: WasteManagerId::generate(),
            accountId: $request->accountId
        ), Action::List, $user);

        $properties = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListWasteManagersResponse($properties, $totalResults);
    }
}