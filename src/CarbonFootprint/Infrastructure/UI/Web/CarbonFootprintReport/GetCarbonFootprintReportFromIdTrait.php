<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;

trait GetCarbonFootprintReportFromIdTrait
{
    private function getCarbonFootprintReportFromId(string $reportId, CarbonFootprintReportRepository $repository): CarbonFootprintReport
    {
        $report = $repository->find(new CarbonFootprintReportId($reportId));

        if (null === $report) {
            throw $this->createNotFoundException('Informe no encontrado');
        }

        return $report;
    }
}
