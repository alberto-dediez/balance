<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;

class CarbonFootprintReportRepositoryMother
{
    public static function inMemory(): InMemoryCarbonFootprintReportRepository
    {
        return new InMemoryCarbonFootprintReportRepository();
    }
}
