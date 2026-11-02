<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkId;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkStatistics;

class SectorBenchmarkMother
{
    public static function computed(
        string $segmentKey = 'Hotel',
        int $year = 2025,
        IntensityIndicator $indicator = IntensityIndicator::PerStay,
        float $median = 3.0,
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
    ): SectorBenchmark {
        return SectorBenchmark::compute(
            SectorBenchmarkId::generate(),
            $subjectType,
            $segmentKey,
            $year,
            $indicator,
            new SectorBenchmarkStatistics(
                sampleSize: SectorBenchmarkStatistics::MIN_SAMPLE_SIZE,
                accountCount: SectorBenchmarkStatistics::MIN_ACCOUNT_COUNT,
                median: $median,
                percentile25: $median * 0.75,
                percentile75: $median * 1.25,
                mean: $median,
            ),
        );
    }
}
