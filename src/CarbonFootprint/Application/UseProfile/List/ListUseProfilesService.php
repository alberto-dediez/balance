<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\List;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListUseProfilesService
{
    public function __construct(private UseProfileRepository $repository, private UseProfileAuthorizer $authorizer) {}

    public function __invoke(ListUseProfilesRequest $request, User $user): ListUseProfilesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new UseProfile(id: UseProfileId::generate()), SecurityAction::List, $user);

        $useProfiles = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListUseProfilesResponse($useProfiles, $totalResults);
    }
}
