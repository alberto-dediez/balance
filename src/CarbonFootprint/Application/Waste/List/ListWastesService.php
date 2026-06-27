<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\List;

use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListWastesService
{
    public function __construct(private WasteRepository $repository, private WasteAuthorizer $authorizer) {}

    public function __invoke(ListWastesRequest $request, User $user): ListWastesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Waste(id: WasteId::generate()), SecurityAction::List, $user);

        $wastes = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListWastesResponse($wastes, $totalResults);
    }
}
