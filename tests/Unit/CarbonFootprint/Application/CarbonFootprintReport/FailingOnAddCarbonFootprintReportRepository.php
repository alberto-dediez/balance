<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;

final class FailingOnAddCarbonFootprintReportRepository extends InMemoryCarbonFootprintReportRepository
{
    private bool $armed = false;

    public function failOnNextAdd(): void
    {
        $this->armed = true;
    }

    public function add(CarbonFootprintReport $report): void
    {
        if ($this->armed) {
            $this->armed = false;

            throw new \RuntimeException('persistence failure');
        }

        parent::add($report);
    }
}
