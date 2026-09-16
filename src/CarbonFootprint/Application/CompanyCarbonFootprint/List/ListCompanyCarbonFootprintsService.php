<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\List;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCompanyCarbonFootprintsService
{
    public function __construct(
        private readonly CompanyCarbonFootprintRepository $repository,
        private readonly CompanyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ListCompanyCarbonFootprintsRequest $request, User $user): ListCompanyCarbonFootprintsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            accountId: $request->accountId,
        ), SecurityAction::List, $user);

        return new ListCompanyCarbonFootprintsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
