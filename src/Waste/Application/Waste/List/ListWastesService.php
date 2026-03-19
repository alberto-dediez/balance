<?php

namespace App\Waste\Application\Waste\List;

use App\Security\Application\Action;
use App\Waste\Domain\Model\Waste\Waste;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\WasteRepository;

class ListWastesService
{
    public function __construct(private readonly WasteRepository $repository, private readonly WasteAuthorizer $authorizer)
    {

    }

    public function __invoke(ListWastesRequest $request, User $user): ListWastesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Waste(id: WasteId::generate()), Action::List, $user);

        $wastes = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListWastesResponse($wastes, $totalResults);
    }
}