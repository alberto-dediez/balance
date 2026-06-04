<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\MonthlyReport;

interface MonthlyReportNotifier
{
    public function send(MonthlyReportResponse $response, \DateTimeImmutable $month): void;
}
