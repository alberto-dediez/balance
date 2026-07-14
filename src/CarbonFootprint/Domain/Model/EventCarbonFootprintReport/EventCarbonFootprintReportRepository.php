<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;

interface EventCarbonFootprintReportRepository
{
    public function add(EventCarbonFootprintReport $report): void;

    public function remove(EventCarbonFootprintReport $report): void;

    public function find(EventCarbonFootprintReportId $id): ?EventCarbonFootprintReport;

    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprintReport;
}
