<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReportThrottle;

class StubErrorReportThrottle implements ErrorReportThrottle
{
    public function __construct(private readonly bool $shouldNotify = true) {}

    public function shouldNotify(string $fingerprint): bool
    {
        return $this->shouldNotify;
    }
}
