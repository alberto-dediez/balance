<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\GasBreakdown;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InventoryLine;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final readonly class InventoryResults
{
    private const float DRIFT_TOLERANCE_KG = 0.01;

    /**
     * @param array<string, float>                                                                           $byScope
     * @param array<string, array<string, float>>                                                            $byScopeAndSource
     * @param array<string, array<string, list<InventoryLine>>>                                              $linesByScopeAndSource
     * @param list<array{scope: Scope, source: Source, kg: float, percent: float, cumulativePercent: float}> $contribution
     */
    public function __construct(
        public float $totalKg,
        public float $recalculatedTotalKg,
        public array $byScope,
        public array $byScopeAndSource,
        public array $linesByScopeAndSource,
        public array $contribution,
        public GasBreakdown $gas,
        public bool $dualReportingActive,
        public float $scope2MarketKg,
        public float $scope2LocationKg,
        public float $locationBasedTotalKg,
        public int $electricityActivitiesWithoutLocationBased,
        public bool $scope3Excluded,
    ) {}

    /** @return list<InventoryLine> */
    public function lines(): array
    {
        $lines = [];

        foreach ($this->linesByScopeAndSource as $bySource) {
            foreach ($bySource as $sourceLines) {
                foreach ($sourceLines as $line) {
                    $lines[] = $line;
                }
            }
        }

        return $lines;
    }

    public function totalDriftKg(): float
    {
        return abs($this->totalKg - $this->recalculatedTotalKg);
    }

    public function hasTotalDrift(): bool
    {
        return $this->totalDriftKg() > self::DRIFT_TOLERANCE_KG;
    }

    public function scopeKg(Scope $scope): float
    {
        return $this->byScope[$scope->value] ?? 0.0;
    }
}
