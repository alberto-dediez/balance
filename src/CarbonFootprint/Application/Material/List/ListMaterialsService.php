<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\List;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMaterialsService
{
    public function __construct(private MaterialRepository $repository, private MaterialAuthorizer $authorizer) {}

    public function __invoke(ListMaterialsRequest $request, User $user): ListMaterialsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Material(id: MaterialId::generate()), SecurityAction::List, $user);

        $materials = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMaterialsResponse($materials, $totalResults);
    }
}
