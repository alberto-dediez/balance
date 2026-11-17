<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;

class ThrowingErrorReportNotifier implements ErrorReportNotifier
{
    public function __invoke(ErrorReport $report): void
    {
        throw new \RuntimeException('notifier failure');
    }
}
