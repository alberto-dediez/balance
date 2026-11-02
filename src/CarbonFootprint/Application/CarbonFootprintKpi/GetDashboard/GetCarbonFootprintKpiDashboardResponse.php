<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard;

use App\CarbonFootprint\Application\CarbonFootprintKpi\InventoryResults;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubject;
use App\CarbonFootprint\Application\CarbonFootprintKpi\PropertyComparisonRow;
use App\CarbonFootprint\Application\CarbonFootprintKpi\TimeSeriesPoint;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorValue;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariation;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Application\CommandBus\CommandResponse;

final readonly class GetCarbonFootprintKpiDashboardResponse implements CommandResponse
{
    public const string NO_COMPLETED_FOOTPRINT = 'no_completed_footprint';
    public const string NO_DENOMINATOR = 'no_denominator';

    /**
     * @param list<int>                                       $availableYears
     * @param list<IntensityIndicatorValue>                   $indicators
     * @param array<string, SectorBenchmark>                  $benchmarks
     * @param list<TimeSeriesPoint>                           $series
     * @param list<PropertyComparisonRow>                     $propertyComparison
     * @param list<array{property: Property, reason: string}> $propertiesWithoutData
     * @param array<int, string>                              $footprintIdsByYear
     */
    public function __construct(
        public KpiSubject $subject,
        public bool $hasData,
        public array $availableYears = [],
        public ?int $selectedYear = null,
        public ?InventoryResults $inventory = null,
        public array $indicators = [],
        public array $benchmarks = [],
        public array $series = [],
        public ?BaseYearDeclaration $baseYear = null,
        public bool $baseYearAlert = false,
        public ?float $baseYearCurrentTotalKg = null,
        public ?InterannualVariation $variationVsPrevious = null,
        public ?InterannualVariation $variationVsBaseYear = null,
        public ?IntensityIndicator $comparisonIndicator = null,
        public array $propertyComparison = [],
        public array $propertiesWithoutData = [],
        public array $footprintIdsByYear = [],
    ) {}

    public function selectedFootprintId(): ?string
    {
        return null !== $this->selectedYear ? ($this->footprintIdsByYear[$this->selectedYear] ?? null) : null;
    }

    public function isBaseYearSelected(): bool
    {
        return null !== $this->baseYear && $this->baseYear->year() === $this->selectedYear;
    }
}
