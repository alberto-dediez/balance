<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\List;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListElectricityMixesService
{
    public function __construct(private ElectricityMixRepository $repository, private ElectricityMixAuthorizer $authorizer) {}

    public function __invoke(ListElectricityMixesRequest $request, User $user): ListElectricityMixesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ElectricityMix(id: ElectricityMixId::generate()), SecurityAction::List, $user);

        $electricityMixes = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListElectricityMixesResponse($electricityMixes, $totalResults);
    }
}
