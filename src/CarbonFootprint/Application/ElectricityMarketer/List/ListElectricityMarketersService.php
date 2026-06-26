<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\List;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListElectricityMarketersService
{
    public function __construct(private ElectricityMarketerRepository $repository, private ElectricityMarketerAuthorizer $authorizer) {}

    public function __invoke(ListElectricityMarketersRequest $request, User $user): ListElectricityMarketersResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ElectricityMarketer(id: ElectricityMarketerId::generate()), SecurityAction::List, $user);

        $electricityMarketers = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListElectricityMarketersResponse($electricityMarketers, $totalResults);
    }
}
