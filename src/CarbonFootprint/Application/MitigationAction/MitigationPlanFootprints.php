<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class MitigationPlanFootprints
{
    public function __construct(
        private CarbonFootprintReportSubjects $subjects,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $propertyCarbonFootprintAuthorizer,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
    ) {}

    public function find(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): CarbonFootprint
    {
        if (CarbonFootprintReportSubjectType::Event === $subjectType) {
            throw new \InvalidArgumentException('Mitigation plans are not available for event carbon footprints');
        }

        $carbonFootprint = $this->subjects->find($subjectType, $carbonFootprintId);

        if (null === $carbonFootprint) {
            throw new \InvalidArgumentException('CarbonFootprint not found');
        }

        return $carbonFootprint;
    }

    public function denyUnlessCanDoThat(
        CarbonFootprintReportSubjectType $subjectType,
        CarbonFootprint $carbonFootprint,
        SecurityAction $action,
        User $user,
    ): void {
        match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => $this->propertyCarbonFootprintAuthorizer->denyUnlessCanDoThat($carbonFootprint, $action, $user),
            CarbonFootprintReportSubjectType::Company => $this->companyCarbonFootprintAuthorizer->denyUnlessCanDoThat($carbonFootprint, $action, $user),
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Mitigation plans are not available for event carbon footprints'),
        };
    }

    public function ensureIsEditable(CarbonFootprint $carbonFootprint): void
    {
        if ($carbonFootprint->isCompleted()) {
            throw new MitigationPlanNotEditableException('The carbon footprint is completed');
        }
    }

    public function previous(CarbonFootprintReportSubjectType $subjectType, CarbonFootprint $carbonFootprint): ?CarbonFootprint
    {
        $candidates = match (true) {
            CarbonFootprintReportSubjectType::Property === $subjectType && $carbonFootprint instanceof PropertyCarbonFootprint && null !== $carbonFootprint->propertyId() => $this->propertyCarbonFootprintRepository->findByPropertyId($carbonFootprint->propertyId()),
            CarbonFootprintReportSubjectType::Company === $subjectType && $carbonFootprint instanceof CompanyCarbonFootprint && null !== $carbonFootprint->companyId() => $this->companyCarbonFootprintRepository->findByCompanyId($carbonFootprint->companyId()),
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
