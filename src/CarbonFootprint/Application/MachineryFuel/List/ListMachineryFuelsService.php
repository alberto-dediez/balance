<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\List;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMachineryFuelsService
{
    public function __construct(private MachineryFuelRepository $repository, private MachineryFuelAuthorizer $authorizer) {}

    public function __invoke(ListMachineryFuelsRequest $request, User $user): ListMachineryFuelsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MachineryFuel(id: MachineryFuelId::generate()), SecurityAction::List, $user);

        $machineryFuels = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMachineryFuelsResponse($machineryFuels, $totalResults);
    }
}
