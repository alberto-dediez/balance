<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\OrganizationalFootprintOverview;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\PropertyEmissions;

final readonly class OrganizationalDashboard
{
    /**
     * @param array<string, float>              $referenceYearByScopeKg   co2eq (kg) indexed by Scope value
     * @param array<int, float>                 $emissionsByYearKg        co2eq (kg) indexed by year
     * @param PropertyEmissions[]               $topPropertiesByEmissions
     * @param OrganizationalFootprintOverview[] $latestFootprints
     */
    public function __construct(
        public int $totalProperties,
        public int $propertiesWithCompletedFootprint,
        public int $totalCompanies,
        public int $companiesWithCompletedFootprint,
        public int $totalFootprints,
        public int $completedFootprints,
        public int $inProgressFootprints,
        public int $footprintsWithMitigationPlan,
        public ?int $referenceYear,
        public float $referenceYearTotalKg,
        public array $referenceYearByScopeKg,
        public ?int $previousYear,
        public ?float $variationVsPreviousYearPercent,
        public array $emissionsByYearKg,
        public array $topPropertiesByEmissions,
        public array $latestFootprints,
    ) {}
}
