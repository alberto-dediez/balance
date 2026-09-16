<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\List;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListPropertyCarbonFootprintsService
{
    public function __construct(
        private readonly PropertyCarbonFootprintRepository $repository,
        private readonly PropertyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ListPropertyCarbonFootprintsRequest $request, User $user): ListPropertyCarbonFootprintsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            accountId: $request->accountId,
        ), SecurityAction::List, $user);

        return new ListPropertyCarbonFootprintsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
