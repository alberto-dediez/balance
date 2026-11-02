<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetMaterialityStepService
{
    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialityAssessmentFootprints $footprints,
    ) {}

    public function __invoke(GetMaterialityStepRequest $request, User $user): GetMaterialityStepResponse
    {
        $carbonFootprint = $this->footprints->find($request->subjectType, $request->carbonFootprintId);

        $this->footprints->denyUnlessCanDoThat($request->subjectType, $carbonFootprint, SecurityAction::View, $user);

        $assessment = $this->materialityAssessmentRepository->findCurrentByCarbonFootprint($request->subjectType, $request->carbonFootprintId);

        $previousYear = null;
        $previousSectorId = null;
        $previousCarbonFootprint = $this->footprints->previous($request->subjectType, $carbonFootprint);

        if (null !== $previousCarbonFootprint) {
            $previous = $this->materialityAssessmentRepository->findCurrentByCarbonFootprint($request->subjectType, (string) $previousCarbonFootprint->id());

            if (null !== $previous && $previous->isCompleted()) {
                $previousYear = $previous->year();
                $previousSectorId = $previous->sectorId();
            }
        }

        return new GetMaterialityStepResponse(
            assessment: $assessment,
            sectors: $this->materialitySectorRepository->findAll(),
            previousYear: $previousYear,
            previousSectorId: $previousSectorId,
            footprintCompleted: $carbonFootprint->isCompleted(),
            materialityAnswered: $this->isMaterialityAnswered($carbonFootprint),
        );
    }

    private function isMaterialityAnswered(CarbonFootprint $carbonFootprint): bool
    {
        return ($carbonFootprint instanceof PropertyCarbonFootprint || $carbonFootprint instanceof CompanyCarbonFootprint)
            && $carbonFootprint->status()->isMaterialityAnswered();
    }
}
