<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use Psr\Log\LoggerInterface;

class ChainErrorReportNotifier implements ErrorReportNotifier
{
    /**
     * @param iterable<ErrorReportNotifier> $notifiers
     */
    public function __construct(
        private readonly iterable $notifiers,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ErrorReport $report): void
    {
        foreach ($this->notifiers as $notifier) {
            try {
                $notifier($report);
            } catch (\Throwable $throwable) {
                $this->logger->error('No se ha podido notificar el error {incident}: {message}', [
                    'incident' => $report->incidentId,
                    'message' => $throwable->getMessage(),
                    'exception' => $throwable,
                ]);
            }
        }
    }
}
