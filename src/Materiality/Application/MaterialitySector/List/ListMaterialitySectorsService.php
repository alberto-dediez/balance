<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\List;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMaterialitySectorsService
{
    public function __construct(
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialitySectorAuthorizer $authorizer,
    ) {}

    public function __invoke(ListMaterialitySectorsRequest $request, User $user): ListMaterialitySectorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new MaterialitySector(id: MaterialitySectorId::generate()),
            SecurityAction::List,
            $user,
        );

        return new ListMaterialitySectorsResponse(
            materialitySectors: $this->materialitySectorRepository->findByFilter($request),
            totalResults: $this->materialitySectorRepository->totalResultsByFilter($request),
        );
    }
}
