<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;

class InMemoryCarbonFootprintReportRepositoryTest extends CarbonFootprintReportRepositoryTest
{
    protected function createSUT(): CarbonFootprintReportRepository
    {
        return CarbonFootprintReportRepositoryMother::inMemory();
    }
}
