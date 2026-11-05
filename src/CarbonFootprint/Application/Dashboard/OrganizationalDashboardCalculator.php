<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\OrganizationalFootprintOverview;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\PropertyEmissions;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;

class OrganizationalDashboardCalculator
{
    private const int TOP_PROPERTIES = 5;

    private const int LATEST_FOOTPRINTS = 5;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
        private MitigationActionRepository $mitigationActionRepository,
        private EmissionsBreakdownBuilder $breakdownBuilder,
    ) {}

    public function __invoke(AccountId $accountId): OrganizationalDashboard
    {
        /** @var array<string, Property> $properties */
        $properties = [];
        foreach ($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0)) as $property) {
            $properties[(string) $property->id()] = $property;
        }

        /** @var array<string, Company> $companies */
        $companies = [];
        foreach ($this->companyRepository->findByFilter(new CompanyFilter(accountId: $accountId, limit: 0)) as $company) {
            $companies[(string) $company->id()] = $company;
        }

        $propertyFootprints = $this->propertyCarbonFootprintRepository->findByFilter(
            new PropertyCarbonFootprintFilter(accountId: $accountId, limit: 0),
        );
        $companyFootprints = $this->companyCarbonFootprintRepository->findByFilter(
            new CompanyCarbonFootprintFilter(accountId: $accountId, limit: 0),
        );

        /** @var array<string, true> $footprintsWithMitigationActions */
        $footprintsWithMitigationActions = [];
        foreach ($this->mitigationActionRepository->findByFilter(new MitigationActionFilter(accountId: $accountId, limit: 0)) as $mitigationAction) {
            $footprintsWithMitigationActions[self::mitigationKey($mitigationAction->subjectType(), $mitigationAction->carbonFootprintId())] = true;
        }

        $completedFootprints = 0;
        $footprintsWithMitigationPlan = 0;
        $propertiesWithCompletedFootprint = [];
        $companiesWithCompletedFootprint = [];
        /** @var array<string, PropertyCarbonFootprint> $latestCompletedByPropertyAndYear */
        $latestCompletedByPropertyAndYear = [];

        foreach ($propertyFootprints as $footprint) {
            if (isset($footprintsWithMitigationActions[self::mitigationKey(CarbonFootprintReportSubjectType::Property, (string) $footprint->id())])) {
                ++$footprintsWithMitigationPlan;
            }

            if (!$footprint->isCompleted()) {
                continue;
            }

            ++$completedFootprints;

            $propertyKey = (string) $footprint->propertyId();
            $propertiesWithCompletedFootprint[$propertyKey] = true;

            $key = $propertyKey . '|' . $footprint->year();
            $current = $latestCompletedByPropertyAndYear[$key] ?? null;

            if (null === $current || self::lastChange($footprint) > self::lastChange($current)) {
                $latestCompletedByPropertyAndYear[$key] = $footprint;
            }
        }

        foreach ($companyFootprints as $footprint) {
            if (isset($footprintsWithMitigationActions[self::mitigationKey(CarbonFootprintReportSubjectType::Company, (string) $footprint->id())])) {
                ++$footprintsWithMitigationPlan;
            }

            if (!$footprint->isCompleted()) {
                continue;
            }

            ++$completedFootprints;
            $companiesWithCompletedFootprint[(string) $footprint->companyId()] = true;
        }

        /** @var array<int, float> $emissionsByYearKg */
        $emissionsByYearKg = [];
        foreach ($latestCompletedByPropertyAndYear as $footprint) {
            $year = $footprint->year();
            $emissionsByYearKg[$year] = ($emissionsByYearKg[$year] ?? 0.0) + (float) $footprint->totalEmissions()->co2eq();
        }

        ksort($emissionsByYearKg);

        $referenceYear = [] === $emissionsByYearKg ? null : max(array_keys($emissionsByYearKg));
        $referenceYearTotalKg = null === $referenceYear ? 0.0 : $emissionsByYearKg[$referenceYear];

        $referenceYearByScopeKg = [];
        $topPropertiesByEmissions = [];

        if (null !== $referenceYear) {
            foreach (Scope::cases() as $scope) {
                $referenceYearByScopeKg[$scope->value] = 0.0;
            }

            foreach ($latestCompletedByPropertyAndYear as $footprint) {
                if ($footprint->year() !== $referenceYear) {
                    continue;
                }

                $breakdown = $this->breakdownBuilder->build($footprint, Scope::cases());
                foreach (Scope::cases() as $scope) {
                    $referenceYearByScopeKg[$scope->value] += (float) $breakdown->byStage[$scope->value]->co2eq();
                }

                $property = $properties[(string) $footprint->propertyId()] ?? null;
                $co2eq = (float) $footprint->totalEmissions()->co2eq();
                if ($property instanceof Property && $co2eq > 0.0) {
                    $topPropertiesByEmissions[] = new PropertyEmissions($property, $co2eq);
                }
            }
        }

        usort($topPropertiesByEmissions, static fn(PropertyEmissions $a, PropertyEmissions $b): int => $b->co2eq <=> $a->co2eq);

        $previousYear = null;
        foreach (array_keys($emissionsByYearKg) as $year) {
            if (null !== $referenceYear && $year < $referenceYear) {
                $previousYear = $year;
            }
        }

        $previousYearTotalKg = null === $previousYear ? 0.0 : $emissionsByYearKg[$previousYear];
        $variationVsPreviousYearPercent = $previousYearTotalKg > 0.0
            ? ($referenceYearTotalKg - $previousYearTotalKg) / $previousYearTotalKg * 100
            : null;

        $totalFootprints = count($propertyFootprints) + count($companyFootprints);

        return new OrganizationalDashboard(
            totalProperties: count($properties),
            propertiesWithCompletedFootprint: count($propertiesWithCompletedFootprint),
            totalCompanies: count($companies),
            companiesWithCompletedFootprint: count($companiesWithCompletedFootprint),
            totalFootprints: $totalFootprints,
            completedFootprints: $completedFootprints,
            inProgressFootprints: $totalFootprints - $completedFootprints,
            footprintsWithMitigationPlan: $footprintsWithMitigationPlan,
            referenceYear: $referenceYear,
            referenceYearTotalKg: $referenceYearTotalKg,
            referenceYearByScopeKg: $referenceYearByScopeKg,
            previousYear: $previousYear,
            variationVsPreviousYearPercent: $variationVsPreviousYearPercent,
            emissionsByYearKg: $emissionsByYearKg,
            topPropertiesByEmissions: array_slice($topPropertiesByEmissions, 0, self::TOP_PROPERTIES),
            latestFootprints: $this->latestFootprints($propertyFootprints, $companyFootprints, $properties, $companies),
        );
    }

    /**
     * @param PropertyCarbonFootprint[] $propertyFootprints
     * @param CompanyCarbonFootprint[]  $companyFootprints
     * @param array<string, Property>   $properties
     * @param array<string, Company>    $companies
     *
     * @return OrganizationalFootprintOverview[]
     */
    private function latestFootprints(array $propertyFootprints, array $companyFootprints, array $properties, array $companies): array
    {
        $footprints = [...$propertyFootprints, ...$companyFootprints];

        usort(
            $footprints,
            static fn(PropertyCarbonFootprint|CompanyCarbonFootprint $a, PropertyCarbonFootprint|CompanyCarbonFootprint $b): int => $b->registeredAt() <=> $a->registeredAt(),
        );

        return array_map(
            static fn(PropertyCarbonFootprint|CompanyCarbonFootprint $footprint): OrganizationalFootprintOverview => $footprint instanceof PropertyCarbonFootprint
                ? new OrganizationalFootprintOverview(
                    $footprint,
                    CarbonFootprintReportSubjectType::Property,
                    ($properties[(string) $footprint->propertyId()] ?? null)?->name(),
                )
                : new OrganizationalFootprintOverview(
                    $footprint,
                    CarbonFootprintReportSubjectType::Company,
                    ($companies[(string) $footprint->companyId()] ?? null)?->name(),
                ),
            array_slice($footprints, 0, self::LATEST_FOOTPRINTS),
        );
    }

    private static function mitigationKey(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): string
    {
        return $subjectType->value . '|' . $carbonFootprintId;
    }

    private static function lastChange(PropertyCarbonFootprint $footprint): \DateTimeImmutable
    {
        return $footprint->updatedAt() ?? $footprint->registeredAt();
    }
}
