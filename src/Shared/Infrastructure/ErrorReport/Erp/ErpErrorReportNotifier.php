<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport\Erp;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Domain\Model\ErrorReport\ErrorReportThrottle;
use Psr\Log\LoggerInterface;

class ErpErrorReportNotifier implements ErrorReportNotifier
{
    public function __construct(
        private readonly ErpErrorReportClient $client,
        private readonly ErpErrorReportPayload $payload,
        private readonly ErrorReportThrottle $throttle,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly string $projectId,
    ) {}

    public function __invoke(ErrorReport $report): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->client->isConfigured() || '' === trim($this->projectId)) {
            $this->logger->warning('El reporte de errores al ERP está activado pero sin configurar; no se envía el error {incident}', [
                'incident' => $report->incidentId,
            ]);

            return;
        }

        try {
            if (!$this->throttle->shouldNotify($report->fingerprint)) {
                return;
            }

            $this->client->send($this->payload->fromReport($report, trim($this->projectId)));
        } catch (\Throwable $throwable) {
            $this->logger->error('No se ha podido reportar al ERP el error {incident}: {message}', [
                'incident' => $report->incidentId,
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);
        }
    }
}
