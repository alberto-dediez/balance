<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Notification;

use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\UserRegistered;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class WelcomeUserNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof UserRegistered);

        $this->handleUserRegistered($event);
    }

    private function handleUserRegistered(UserRegistered $event): void
    {
        $subject = 'Bienvenido {user}';
        $subjectParams = ['{user}' => $event->username()];
        $params = [
            'username' => $event->username(),
            'account' => $event->accountId(),
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $event->userId(),
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@security/users/welcome.notification.html.twig',
            params: $params,
            accountId: $event->accountId(),
            subjectParams: $subjectParams,
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $event->userId(),
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@security/users/welcome.email.html.twig',
            params: $params,
            accountId: $event->accountId(),
            subjectParams: $subjectParams,
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof UserRegistered;
    }
}
