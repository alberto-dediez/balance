<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\List;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCombustiblesService
{
    public function __construct(private CombustibleRepository $repository, private CombustibleAuthorizer $authorizer) {}

    public function __invoke(ListCombustiblesRequest $request, User $user): ListCombustiblesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Combustible(id: CombustibleId::generate()), SecurityAction::List, $user);

        $combustibles = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCombustiblesResponse($combustibles, $totalResults);
    }
}
