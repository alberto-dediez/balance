<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\GetPlan;

use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjector;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetMitigationPlanService
{
    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationPlanFootprints $footprints,
        private MitigationEmissionSourcesBuilder $emissionSourcesBuilder,
    ) {}

    public function __invoke(GetMitigationPlanRequest $request, User $user): GetMitigationPlanResponse
    {
        $carbonFootprint = $this->footprints->find($request->subjectType, $request->carbonFootprintId);

        $this->footprints->denyUnlessCanDoThat($request->subjectType, $carbonFootprint, SecurityAction::View, $user);

        $actions = array_values($this->mitigationActionRepository->findByFilter(new MitigationActionFilter(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            limit: 0,
        )));

        $emissionSources = $this->emissionSourcesBuilder->build($carbonFootprint);

        return new GetMitigationPlanResponse(
            actions: $actions,
            emissionSources: $emissionSources,
            projection: (new MitigationProjector())->project($carbonFootprint->calculationYear(), $emissionSources, $actions),
            hasPreviousFootprint: null !== $this->footprints->previous($request->subjectType, $carbonFootprint),
        );
    }
}
