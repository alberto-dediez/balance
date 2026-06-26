<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\List;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMobilitiesService
{
    public function __construct(private MobilityRepository $repository, private MobilityAuthorizer $authorizer) {}

    public function __invoke(ListMobilitiesRequest $request, User $user): ListMobilitiesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Mobility(id: MobilityId::generate()), SecurityAction::List, $user);

        $mobilities = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMobilitiesResponse($mobilities, $totalResults);
    }
}
