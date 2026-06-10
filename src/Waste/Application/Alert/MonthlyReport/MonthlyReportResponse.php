<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\MonthlyReport;

class MonthlyReportResponse
{
    /** @param array<string, array<\App\Waste\Domain\Model\Alert\Alert>> $alertsByAccount */
    public function __construct(
        public readonly array $alertsByAccount = [],
        public readonly int $totalAlerts = 0,
    ) {}
}
