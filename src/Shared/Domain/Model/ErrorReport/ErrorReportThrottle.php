<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\ErrorReport;

interface ErrorReportThrottle
{
    public function shouldNotify(string $fingerprint): bool;
}
