<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\ErrorReport;

class ErrorReport
{
    /**
     * @param ErrorReportException[] $previous
     */
    public function __construct(
        public string $incidentId,
        public string $fingerprint,
        public int $statusCode,
        public ErrorReportException $exception,
        public ErrorReportContext $context,
        public ?ErrorReportRequest $request = null,
        public ?string $command = null,
        public array $previous = [],
        public string $environment = '',
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable('NOW'),
    ) {}
}
