<?php

declare(strict_types=1);

namespace App\Security\Domain\Event\EmailLog;

use App\Security\Application\EmailLog\Register\RegisterEmailLogRequest;
use App\Security\Application\EmailLog\Register\RegisterEmailLogService;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailSendingFailed;
use App\Shared\Domain\Model\Email\EmailSent;
use Psr\Log\LoggerInterface;

class RecordEmailLogOnEmailSent implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RegisterEmailLogService $registerEmailLog,
        private LoggerInterface $logger,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EmailSent || $event instanceof EmailSendingFailed);

        $registerEmailLog = $this->registerEmailLog;

        try {
            $registerEmailLog($this->requestFrom($event));
        } catch (\Throwable $e) {
            $this->logger->error('No se pudo registrar el email enviado', [
                'trackingId' => (string) $event->trackingId(),
                'exception' => $e,
            ]);
        }
    }

    private function requestFrom(EmailSent|EmailSendingFailed $event): RegisterEmailLogRequest
    {
        $isSent = $event instanceof EmailSent;

        return new RegisterEmailLogRequest(
            emailLogId: (string) $event->trackingId(),
            recipientEmail: $event->recipient()->email,
            recipientName: $event->recipient()->name,
            subject: $event->subject(),
            template: $event->content(),
            provider: $event->provider(),
            providerMessageId: $isSent ? $event->providerMessageId() : null,
            status: $isSent ? EmailLogStatus::Sent : EmailLogStatus::Failed,
            statusReason: $isSent ? null : $event->reason(),
            sentAt: $event->occurredOn(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EmailSent || $event instanceof EmailSendingFailed;
    }
}
