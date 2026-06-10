<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Notification;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRegistered;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Sms\SmsSender;
use App\Shared\Domain\Model\WhatsApp\WhatsAppSender;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SignatureRequestedMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private SmsSender $smsSender,
        private WhatsAppSender $whatsAppSender,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof SignatureRequestRegistered);

        $this->handleSignatureRequested($event);
    }

    private function handleSignatureRequested(SignatureRequestRegistered $event): void
    {
        $signer = $event->signer();

        $signingUrl = $this->urlGenerator->generate(
            'public_sign_view',
            ['token' => $event->token()->plainValue()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        if (NotificationChannel::Sms === $signer->notificationChannel()) {
            if (null === $signer->phone()) {
                return;
            }

            $body = $this->translator->trans(
                'Firma el documento aquí: {url}',
                ['{url}' => $signingUrl],
            );
            ($this->smsSender)($signer->phone(), $body);

            return;
        }

        if (NotificationChannel::Whatsapp === $signer->notificationChannel()) {
            if (null === $signer->phone()) {
                return;
            }

            $body = $this->translator->trans(
                'Firma el documento aquí: {url}',
                ['{url}' => $signingUrl],
            );
            ($this->whatsAppSender)($signer->phone(), $body);

            return;
        }

        if (null === $signer->email()) {
            return;
        }

        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $signer->name(), email: $signer->email()),
            subject: $this->translator->trans('Solicitud de firma de documento'),
            template: '@security/public/sign/request.email.html.twig',
            params: [
                'signerName' => $signer->name(),
                'signingUrl' => $signingUrl,
                'expiresAt' => $event->expiresAt(),
            ],
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SignatureRequestRegistered;
    }
}
