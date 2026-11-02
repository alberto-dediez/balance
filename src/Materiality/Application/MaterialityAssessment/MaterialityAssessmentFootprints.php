<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class MaterialityAssessmentFootprints
{
    public function __construct(
        private CarbonFootprintReportSubjects $subjects,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $propertyCarbonFootprintAuthorizer,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
    ) {}

    public function find(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId): CarbonFootprint
    {
        $carbonFootprint = $this->subjects->find($subjectType->toReportSubjectType(), $carbonFootprintId);

        if (null === $carbonFootprint) {
            throw new \InvalidArgumentException('CarbonFootprint not found');
        }

        return $carbonFootprint;
    }

    public function denyUnlessCanDoThat(
        MaterialityAssessmentSubjectType $subjectType,
        CarbonFootprint $carbonFootprint,
        SecurityAction $action,
        User $user,
    ): void {
        match ($subjectType) {
            MaterialityAssessmentSubjectType::Property => $this->propertyCarbonFootprintAuthorizer->denyUnlessCanDoThat($carbonFootprint, $action, $user),
            MaterialityAssessmentSubjectType::Company => $this->companyCarbonFootprintAuthorizer->denyUnlessCanDoThat($carbonFootprint, $action, $user),
        };
    }

    public function ensureIsEditable(CarbonFootprint $carbonFootprint): void
    {
        if ($carbonFootprint->isCompleted()) {
            throw new MaterialityAssessmentNotEditableException('The carbon footprint is completed');
        }
    }

    public function ensureScope2Answered(CarbonFootprint $carbonFootprint): void
    {
        $scope2Answered = ($carbonFootprint instanceof PropertyCarbonFootprint || $carbonFootprint instanceof CompanyCarbonFootprint)
            && $carbonFootprint->status()->isScope2Answered();

        if (!$scope2Answered) {
            throw new MaterialityAssessmentScope2NotAnsweredException('The carbon footprint scope 2 is not answered');
        }
    }

    public function previous(MaterialityAssessmentSubjectType $subjectType, CarbonFootprint $carbonFootprint): ?CarbonFootprint
    {
        $candidates = match (true) {
            MaterialityAssessmentSubjectType::Property === $subjectType && $carbonFootprint instanceof PropertyCarbonFootprint && null !== $carbonFootprint->propertyId() => $this->propertyCarbonFootprintRepository->findByPropertyId($carbonFootprint->propertyId()),
            MaterialityAssessmentSubjectType::Company === $subjectType && $carbonFootprint instanceof CompanyCarbonFootprint && null !== $carbonFootprint->companyId() => $this->companyCarbonFootprintRepository->findByCompanyId($carbonFootprint->companyId()),
            default => [],
        };

        $previous = null;

        foreach ($candidates as $candidate) {
            if ($candidate->isRemoved() || $candidate->calculationYear() >= $carbonFootprint->calculationYear()) {
                continue;
            }

            if (null === $previous || $candidate->calculationYear() > $previous->calculationYear()) {
                $previous = $candidate;
            }
        }

        return $previous;
    }
}
