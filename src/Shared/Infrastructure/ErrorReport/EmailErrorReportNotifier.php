<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Domain\Model\ErrorReport\ErrorReportThrottle;
use Psr\Log\LoggerInterface;

class EmailErrorReportNotifier implements ErrorReportNotifier
{
    private const string TEMPLATE = '@shared/mail/error_report.html.twig';

    private const int MAX_SUBJECT_MESSAGE_LENGTH = 120;

    /** @var string[] */
    private readonly array $recipients;

    public function __construct(
        private readonly EmailSender $emailSender,
        private readonly ErrorReportThrottle $throttle,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        string $recipients,
    ) {
        $this->recipients = array_values(array_filter(array_map('trim', explode(',', $recipients))));
    }

    public function __invoke(ErrorReport $report): void
    {
        if (!$this->enabled || [] === $this->recipients) {
            return;
        }

        try {
            if (!$this->throttle->shouldNotify($report->fingerprint)) {
                return;
            }

            foreach ($this->recipients as $recipient) {
                ($this->emailSender)(
                    new EmailRecipient($recipient, $recipient),
                    $this->subject($report),
                    self::TEMPLATE,
                    ['report' => $report],
                );
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('No se ha podido notificar el error {incident}: {message}', [
                'incident' => $report->incidentId,
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);
        }
    }

    private function subject(ErrorReport $report): string
    {
        return sprintf(
            '[%s] Error %d - %s: %s (#%s)',
            $report->environment,
            $report->statusCode,
            $this->shortClass($report->exception->class),
            substr($report->exception->message, 0, self::MAX_SUBJECT_MESSAGE_LENGTH),
            $report->incidentId,
        );
    }

    private function shortClass(string $class): string
    {
        $position = strrpos($class, '\\');

        return false === $position ? $class : substr($class, $position + 1);
    }
}
