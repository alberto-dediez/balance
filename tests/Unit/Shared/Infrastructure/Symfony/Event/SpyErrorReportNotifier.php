<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;

class SpyErrorReportNotifier implements ErrorReportNotifier
{
    private ?ErrorReport $report = null;

    public function __invoke(ErrorReport $report): void
    {
        $this->report = $report;
    }

    public function lastReport(): ?ErrorReport
    {
        return $this->report;
    }
}
