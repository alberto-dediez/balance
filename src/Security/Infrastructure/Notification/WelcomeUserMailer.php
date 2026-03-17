<?php

namespace App\Security\Infrastructure\Notification;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Security\Domain\Model\User\UserRegistered;
use App\Shared\Domain\Event\DomainEventSubscriber;
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeUserMailer implements DomainEventSubscriber
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

        assert($event instanceof UserRegistered);

        $this->handlePasswordRecoveryInitialized($event);
    }

    private function handlePasswordRecoveryInitialized(UserRegistered $event)
    {
        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $event->username(), email: $event->username()),
            subject: $this->translator->trans('Bienvenido {user}', ['{user}' => $event->username()]),
            template: '@security/users/welcome.email.html.twig',
            params: [
                'username' => $event->username(),
                'account' => $event->accountId()
            ]
        );
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return $event instanceof UserRegistered;
    }
}
