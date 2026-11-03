<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportEmployeeSurvey;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Model\Locale\Locale;

final class PropertyCarbonFootprintReportDataCollector implements CarbonFootprintReportDataCollector
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
        private CountryRepository $countryRepository,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private OrganizationalCarbonFootprintReportLinesBuilder $linesBuilder,
        private MitigationPlanReportBuilder $mitigationPlanBuilder,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
        private OrganizationalEmployeeParticipationRepository $employeeParticipationRepository,
        private MaterialityAnnexBuilder $materialityAnnexBuilder,
    ) {}

    public function supports(CarbonFootprintReportSubjectType $subjectType): bool
    {
        return CarbonFootprintReportSubjectType::Property === $subjectType;
    }

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData
    {
        return $this->collectByCarbonFootprintId($report->carbonFootprintId(), $report->baseLocale());
    }

    public function collectByCarbonFootprintId(string $carbonFootprintId, ?Locale $locale = null): CarbonFootprintReportData
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find(new PropertyCarbonFootprintId($carbonFootprintId));

        if (null === $propertyCarbonFootprint) {
            throw new \RuntimeException('PropertyCarbonFootprint not found');
        }

        $propertyId = $propertyCarbonFootprint->propertyId();
        $property = null !== $propertyId ? $this->propertyRepository->find($propertyId) : null;

        if (null === $property) {
            throw new \RuntimeException('Property not found');
        }

        $emissionFactorGroupId = $propertyCarbonFootprint->emissionFactorGroupId();
        $emissionFactorGroup = null !== $emissionFactorGroupId ? $this->emissionFactorGroupRepository->find($emissionFactorGroupId) : null;

        if (null === $emissionFactorGroup) {
            throw new \RuntimeException('EmissionFactorGroup not found');
        }

        $companyId = $property->companyId();
        $countryId = $property->info()->countryId();

        $breakdown = $this->breakdownBuilder->build($propertyCarbonFootprint, Scope::cases());

        $employeeParticipation = $this->employeeParticipationRepository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, (string) $propertyCarbonFootprint->id());

        return new OrganizationalCarbonFootprintReportData(
            subjectType: CarbonFootprintReportSubjectType::Property,
            subjectName: $property->name(),
            companyName: null !== $companyId ? $this->companyRepository->find($companyId)?->name() : null,
            countryName: null !== $countryId ? $this->countryRepository->find($countryId)?->name() : null,
            emissionFactorGroupName: $emissionFactorGroup->name(),
            calculationYear: $propertyCarbonFootprint->calculationYear(),
            totalEmissions: $propertyCarbonFootprint->totalEmissions(),
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            locationBasedTotalEmissions: $breakdown->locationBasedTotal,
            electricityActivitiesWithoutLocationBased: $breakdown->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $breakdown->bySource,
            emissionsByScope: $breakdown->byStage,
            emissionsByScopeAndSource: $breakdown->byStageAndSource,
            emissionsByProperty: [],
            activityLines: $this->linesBuilder->activityLines($propertyCarbonFootprint, []),
            exclusionLines: $this->linesBuilder->exclusionLines($propertyCarbonFootprint),
            mitigationPlan: $this->mitigationPlanBuilder->build(CarbonFootprintReportSubjectType::Property, $propertyCarbonFootprint, $locale),
            scope3Excluded: $propertyCarbonFootprint->isStageExcluded(Scope::Scope3),
            scope3ExclusionReason: $propertyCarbonFootprint->stageExclusionReason(Scope::Scope3),
            scope3ExclusionComment: $propertyCarbonFootprint->stageExclusionComment(Scope::Scope3),
            derivedLines: $this->derivedSourceEmissionsCalculator->lines($propertyCarbonFootprint, Scope::Scope3),
            precalculated: $propertyCarbonFootprint->isPrecalculated(),
            gwpCh4: $emissionFactorGroup->gwp()->gwpCh4(),
            gwpN2O: $emissionFactorGroup->gwp()->gwpN2O(),
            operatingRegime: $property->operatingRegime(),
            employeeSurvey: null === $employeeParticipation ? null : OrganizationalCarbonFootprintReportEmployeeSurvey::fromParticipation($employeeParticipation),
            materialityAnnex: $this->materialityAnnexBuilder->build($propertyCarbonFootprint, CarbonFootprintReportSubjectType::Property),
        );
    }
}
