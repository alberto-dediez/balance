<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;

final class CarbonFootprintReportResultsRoute
{
    public function __construct(private readonly EventCarbonFootprintRepository $eventCarbonFootprintRepository) {}

    /** @return array{route: string, params: array<string, string>} */
    public function resolve(CarbonFootprintReport $report): array
    {
        return match ($report->subjectType()) {
            CarbonFootprintReportSubjectType::Event => [
                'route' => 'events_carbon_footprint_results',
                'params' => ['eventId' => (string) $this->eventCarbonFootprintRepository->find(new EventCarbonFootprintId($report->carbonFootprintId()))?->eventId()],
            ],
            CarbonFootprintReportSubjectType::Property => [
                'route' => 'property_carbon_footprints_results',
                'params' => ['propertyCarbonFootprintId' => $report->carbonFootprintId()],
            ],
            CarbonFootprintReportSubjectType::Company => [
                'route' => 'company_carbon_footprints_results',
                'params' => ['companyCarbonFootprintId' => $report->carbonFootprintId()],
            ],
        };
    }
}
