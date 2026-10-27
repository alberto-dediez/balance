<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;

trait ReportTextsTrait
{
    /** @param array<string, string> $sections */
    private function textsFrom(CarbonFootprintReport $report, array $sections): CarbonFootprintReportTexts
    {
        $current = $report->texts();
        $texts = [];

        $keys = [...CarbonFootprintReportSectionKey::requiredFor($report->subjectType()), ...CarbonFootprintReportSectionKey::optionalFor($report->subjectType())];

        foreach ($keys as $key) {
            if (array_key_exists($key->value, $sections)) {
                $texts[$key->value] = $sections[$key->value];
            } elseif ($current->has($key)) {
                $texts[$key->value] = $current->get($key);
            }
        }

        return new CarbonFootprintReportTexts($texts);
    }
}
