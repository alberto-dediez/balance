<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;

interface SectorBenchmarkRepository
{
    public function add(SectorBenchmark $benchmark): void;

    public function removeAll(): void;

    /** @param list<SectorBenchmark> $benchmarks */
    public function replaceAll(array $benchmarks): void;

    public function findBySegment(CarbonFootprintReportSubjectType $subjectType, string $segmentKey, int $year, IntensityIndicator $indicator): ?SectorBenchmark;

    /** @return list<SectorBenchmark> */
    public function findAll(): array;
}
