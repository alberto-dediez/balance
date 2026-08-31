<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\ErrorReport;

interface ErrorReportNotifier
{
    public function __invoke(ErrorReport $report): void;
}
