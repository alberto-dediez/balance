<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\List;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListRefrigerantGasesService
{
    public function __construct(private RefrigerantGasRepository $repository, private RefrigerantGasAuthorizer $authorizer) {}

    public function __invoke(ListRefrigerantGasesRequest $request, User $user): ListRefrigerantGasesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new RefrigerantGas(id: RefrigerantGasId::generate()), SecurityAction::List, $user);

        $refrigerantGases = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListRefrigerantGasesResponse($refrigerantGases, $totalResults);
    }
}
