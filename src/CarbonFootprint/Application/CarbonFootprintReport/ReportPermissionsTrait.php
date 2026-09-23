<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountReportPermissions;

trait ReportPermissionsTrait
{
    private function reportPermissions(CarbonFootprintReport $report): AccountReportPermissions
    {
        $accountId = $report->accountId();
        $account = $accountId instanceof AccountId ? $this->accountRepository->find($accountId) : null;

        return $account?->reportPermissions() ?? new AccountReportPermissions();
    }

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
