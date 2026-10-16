<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\List;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEconomicSectorsService
{
    public function __construct(private EconomicSectorRepository $repository, private EconomicSectorAuthorizer $authorizer) {}

    public function __invoke(ListEconomicSectorsRequest $request, User $user): ListEconomicSectorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new EconomicSector(id: EconomicSectorId::generate()), SecurityAction::List, $user);

        $economicSectors = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListEconomicSectorsResponse($economicSectors, $totalResults);
    }
}
