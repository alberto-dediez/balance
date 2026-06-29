<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\List;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListGdOFactorsService
{
    public function __construct(private GdOFactorRepository $repository, private GdOFactorAuthorizer $authorizer) {}

    public function __invoke(ListGdOFactorsRequest $request, User $user): ListGdOFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new GdOFactor(id: GdOFactorId::generate()), SecurityAction::List, $user);

        $gdOFactors = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListGdOFactorsResponse($gdOFactors, $totalResults);
    }
}
