<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\PropertyRepository;

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
    ) {}

    public function supports(CarbonFootprintReportSubjectType $subjectType): bool
    {
        return CarbonFootprintReportSubjectType::Property === $subjectType;
    }

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find(new PropertyCarbonFootprintId($report->carbonFootprintId()));

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

        return new OrganizationalCarbonFootprintReportData(
            subjectType: CarbonFootprintReportSubjectType::Property,
            subjectName: $property->name(),
            companyName: null !== $companyId ? $this->companyRepository->find($companyId)?->name() : null,
            countryName: null !== $countryId ? $this->countryRepository->find($countryId)?->name() : null,
            emissionFactorGroupName: $emissionFactorGroup->name(),
            calculationYear: $propertyCarbonFootprint->calculationYear(),
            totalEmissions: $propertyCarbonFootprint->totalEmissions(),
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            emissionsBySource: $breakdown->bySource,
            emissionsByScope: $breakdown->byStage,
            emissionsByScopeAndSource: $breakdown->byStageAndSource,
            emissionsByProperty: [],
            activityLines: $this->linesBuilder->activityLines($propertyCarbonFootprint, []),
            exclusionLines: $this->linesBuilder->exclusionLines($propertyCarbonFootprint),
            mitigationPlan: $this->mitigationPlanBuilder->build(CarbonFootprintReportSubjectType::Property, $propertyCarbonFootprint, $report->baseLocale()),
        );
    }
}
