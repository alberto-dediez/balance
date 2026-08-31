<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Infrastructure\ErrorReport\ErrorReportFactory;
use App\Shared\Infrastructure\ErrorReport\IncidentIdHolder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener]
class ExceptionListener
{
    public function __construct(
        private readonly ErrorReportFactory $errorReportFactory,
        private readonly ErrorReportNotifier $errorReportNotifier,
        private readonly IncidentIdHolder $incidentIdHolder,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $statusCode = $this->statusCode($throwable);

        $report = $this->errorReportFactory->fromRequest($throwable, $statusCode, $event->getRequest());

        $this->incidentIdHolder->set($report->incidentId);

        if ($statusCode < Response::HTTP_INTERNAL_SERVER_ERROR) {
            return;
        }

        ($this->errorReportNotifier)($report);
    }

    private function statusCode(\Throwable $throwable): int
    {
        return $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
