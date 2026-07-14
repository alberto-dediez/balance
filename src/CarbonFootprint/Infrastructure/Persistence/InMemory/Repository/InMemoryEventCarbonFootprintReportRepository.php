<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEventCarbonFootprintReportRepository implements EventCarbonFootprintReportRepository
{
    /** @param array<string, EventCarbonFootprintReport> $reports */
    public function __construct(
        private array $reports = [],
    ) {}

    public function add(EventCarbonFootprintReport $report): void
    {
        $this->reports[(string) $report->id()] = $report;
    }

    public function remove(EventCarbonFootprintReport $report): void
    {
        unset($this->reports[(string) $report->id()]);
    }

    public function find(EventCarbonFootprintReportId $id): ?EventCarbonFootprintReport
    {
        return $this->reports[(string) $id] ?? null;
    }

    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprintReport
    {
        foreach ($this->reports as $report) {
            $subjectId = $report->eventCarbonFootprintId();

            if (null !== $subjectId && $subjectId->equals($eventCarbonFootprintId)) {
                return $report;
            }
        }

        return null;
    }
}
