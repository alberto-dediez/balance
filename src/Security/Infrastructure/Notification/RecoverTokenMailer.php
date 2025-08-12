<?php

namespace App\Security\Infrastructure\Notification;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Event\DomainEventSubscriber;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Security\Domain\Model\User\PasswordRecoveryInitialized;
use App\Shared\Domain\Event\RegisterTrait;

class RecoverTokenMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender, 
        private TranslatorInterface $translator
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event)
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        $this->handlePasswordRecoveryInitialized($event);
    }

    private function handlePasswordRecoveryInitialized(PasswordRecoveryInitialized $event)
    {
        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $event->username(), email: $event->username()),
            subject: $this->translator->trans('Instrucciones para recuperar contraseña'),
            template: '@security/password/recover-token.email.html.twig',
            params: [
                'username' => $event->username(),
                'token' => $event->token()
            ]
        );
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return $event instanceof PasswordRecoveryInitialized;
    }
}