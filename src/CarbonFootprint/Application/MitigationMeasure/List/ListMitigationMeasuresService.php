<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\List;

use App\CarbonFootprint\Application\MitigationMeasure\MitigationMeasureAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMitigationMeasuresService
{
    public function __construct(private MitigationMeasureRepository $repository, private MitigationMeasureAuthorizer $authorizer) {}

    public function __invoke(ListMitigationMeasuresRequest $request, User $user): ListMitigationMeasuresResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MitigationMeasure(id: MitigationMeasureId::generate()), SecurityAction::List, $user);

        $mitigationMeasures = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMitigationMeasuresResponse($mitigationMeasures, $totalResults);
    }
}
