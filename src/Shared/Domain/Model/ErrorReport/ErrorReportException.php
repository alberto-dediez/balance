<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\ErrorReport;

class ErrorReportException
{
    public function __construct(
        public string $class,
        public string $message,
        public string $file,
        public int $line,
        public string $trace = '',
    ) {}
}
