<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Notification;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpRequested;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Sms\SmsSender;
use Symfony\Contracts\Translation\TranslatorInterface;

class SignatureOtpNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private SmsSender $smsSender,
        private TranslatorInterface $translator,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof SignatureOtpRequested);

        $this->notify($event);
    }

    private function notify(SignatureOtpRequested $event): void
    {
        $plainCode = $event->otpChallenge()->plainCode();

        if ('' === $plainCode) {
            return;
        }

        $destination = $event->actualDestination();

        if ('' === $destination) {
            return;
        }

        if (NotificationChannel::Sms === $event->notificationChannel()) {
            $body = $this->translator->trans(
                'Tu código de verificación es: {code}. Válido durante 15 minutos.',
                ['{code}' => $plainCode],
            );
            ($this->smsSender)($destination, $body);

            return;
        }

        ($this->emailSender)(
            recipient: new EmailRecipient(name: $destination, email: $destination),
            subject: $this->translator->trans('Código de verificación'),
            template: '@security/public/sign/otp-email.html.twig',
            params: [
                'otpCode' => $plainCode,
                'expiresAt' => $event->expiresAt(),
            ],
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SignatureOtpRequested;
    }
}
