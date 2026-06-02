<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\MonthlyReport;

class MonthlyReportRequest
{
    public function __construct(
        public readonly \DateTimeImmutable $month,
    ) {}
}
