<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CompanyCarbonFootprintReportDataCollector implements CarbonFootprintReportDataCollector
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyRepository $companyRepository,
        private PropertyRepository $propertyRepository,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private OrganizationalCarbonFootprintReportLinesBuilder $linesBuilder,
        private TranslatorInterface $translator,
    ) {}

    public function supports(CarbonFootprintReportSubjectType $subjectType): bool
    {
        return CarbonFootprintReportSubjectType::Company === $subjectType;
    }

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find(new CompanyCarbonFootprintId($report->carbonFootprintId()));

        if (null === $companyCarbonFootprint) {
            throw new \RuntimeException('CompanyCarbonFootprint not found');
        }

        $companyId = $companyCarbonFootprint->companyId();
        $company = null !== $companyId ? $this->companyRepository->find($companyId) : null;

        if (null === $company) {
            throw new \RuntimeException('Company not found');
        }

        $emissionFactorGroupId = $companyCarbonFootprint->emissionFactorGroupId();
        $emissionFactorGroup = null !== $emissionFactorGroupId ? $this->emissionFactorGroupRepository->find($emissionFactorGroupId) : null;

        if (null === $emissionFactorGroup) {
            throw new \RuntimeException('EmissionFactorGroup not found');
        }

        $propertyNamesById = [];
        foreach ($this->propertyRepository->findByCompanyId($company->id()) as $property) {
            $propertyNamesById[(string) $property->id()] = $property->name();
        }

        $breakdown = $this->breakdownBuilder->build($companyCarbonFootprint, Scope::cases());

        $emissionsByProperty = [];
        foreach ($propertyNamesById as $propertyId => $name) {
            $emissionsByProperty[] = ['propertyId' => $propertyId, 'name' => $name, 'emissions' => $breakdown->byPropertyId[$propertyId] ?? new Emissions()];
        }

        $unassigned = $breakdown->byPropertyId[''] ?? new Emissions();

        if ((float) $unassigned->co2eq() > 0.0) {
            $emissionsByProperty[] = [
                'propertyId' => null,
                'name' => $this->translator->trans('Sin asignar a propiedad', [], 'messages', $report->baseLocale()?->value),
                'emissions' => $unassigned,
            ];
        }

        return new OrganizationalCarbonFootprintReportData(
            subjectType: CarbonFootprintReportSubjectType::Company,
            subjectName: $company->name(),
            companyName: null,
            countryName: null,
            emissionFactorGroupName: $emissionFactorGroup->name(),
            calculationYear: $companyCarbonFootprint->calculationYear(),
            totalEmissions: $companyCarbonFootprint->totalEmissions(),
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            emissionsBySource: $breakdown->bySource,
            emissionsByScope: $breakdown->byStage,
            emissionsByScopeAndSource: $breakdown->byStageAndSource,
            emissionsByProperty: $emissionsByProperty,
            activityLines: $this->linesBuilder->activityLines($companyCarbonFootprint, $propertyNamesById),
            exclusionLines: $this->linesBuilder->exclusionLines($companyCarbonFootprint),
        );
    }
}
