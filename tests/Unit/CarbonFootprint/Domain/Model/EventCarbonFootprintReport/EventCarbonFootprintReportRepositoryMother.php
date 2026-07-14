<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintReportRepository;

class EventCarbonFootprintReportRepositoryMother
{
    public static function inMemory(): InMemoryEventCarbonFootprintReportRepository
    {
        return new InMemoryEventCarbonFootprintReportRepository();
    }
}
