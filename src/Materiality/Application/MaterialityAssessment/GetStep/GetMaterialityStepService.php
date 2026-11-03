<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryRow;
use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryTopicInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopic;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetMaterialityStepService
{
    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialityAssessmentFootprints $footprints,
        private MaterialitySummaryBuilder $summaryBuilder,
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
            summaryRows: $this->summaryRows($carbonFootprint, $request->subjectType, $assessment),
            stageExcluded: $carbonFootprint->isStageExcluded(Scope::Scope3),
            pendingReview: $carbonFootprint->hasPendingMaterialityReview(),
        );
    }

    /** @return list<MaterialitySummaryRow> */
    private function summaryRows(CarbonFootprint $carbonFootprint, MaterialityAssessmentSubjectType $subjectType, ?MaterialityAssessment $assessment): array
    {
        if (null === $assessment || (!$assessment->isCompleted() && [] === $carbonFootprint->materialityDecisions())) {
            return [];
        }

        return $this->summaryBuilder->build(
            $carbonFootprint,
            $subjectType->toReportSubjectType(),
            array_map(
                static fn(MaterialityAssessmentTopic $topic) => new MaterialitySummaryTopicInput(
                    topicId: (string) $topic->topicId(),
                    topicNumber: MaterialityScope::Scope3 === $topic->scope() ? $topic->topicNumber() : null,
                    scorePercent: $topic->scorePercent(),
                    thresholdPercent: $topic->thresholdPercent(),
                    forcedByHighMagnitude: $topic->isForcedByHighMagnitude(),
                    verdict: $topic->verdict(),
                ),
                $assessment->topics(),
            ),
        );
    }

    private function isMaterialityAnswered(CarbonFootprint $carbonFootprint): bool
    {
        return ($carbonFootprint instanceof PropertyCarbonFootprint || $carbonFootprint instanceof CompanyCarbonFootprint)
            && $carbonFootprint->status()->isMaterialityAnswered();
    }
}
