<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class CarbonFootprintReportDataCollectorRegistry
{
    /** @var list<CarbonFootprintReportDataCollector> */
    private array $collectors = [];

    /** @param iterable<CarbonFootprintReportDataCollector> $collectors */
    public function __construct(
        #[AutowireIterator('carbon_footprint.report_data_collector')]
        iterable $collectors,
    ) {
        foreach ($collectors as $collector) {
            $this->collectors[] = $collector;
        }
    }

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData
    {
        foreach ($this->collectors as $collector) {
            if ($collector->supports($report->subjectType())) {
                return $collector->collect($report);
            }
        }

        throw new \InvalidArgumentException(sprintf('No report data collector registered for subject type "%s".', $report->subjectType()->value));
    }
}
