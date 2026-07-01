<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\List;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorGroupsService
{
    public function __construct(private EmissionFactorGroupRepository $repository, private EmissionFactorGroupAuthorizer $authorizer) {}

    public function __invoke(ListEmissionFactorGroupsRequest $request, User $user): ListEmissionFactorGroupsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new EmissionFactorGroup(id: EmissionFactorGroupId::generate()), SecurityAction::List, $user);

        $emissionFactorGroups = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListEmissionFactorGroupsResponse($emissionFactorGroups, $totalResults);
    }
}
