<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Notification;

use App\Library\Domain\Model\StorageSync\MediaStorageSynchronized;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class MediaStorageSynchronizedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly SystemNotifier $notifier,
        private readonly UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof MediaStorageSynchronized) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->handleMediaStorageSynchronized($event);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MediaStorageSynchronized;
    }

    private function handleMediaStorageSynchronized(MediaStorageSynchronized $event): void
    {
        foreach ($this->recipients() as $recipient) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $recipient->id(),
                channel: NotificationChannel::Internal,
                subject: 'El almacenamiento de la biblioteca se ha recuperado',
                template: '@library/storage/synchronized.notification.html.twig',
                params: [
                    'uploaded' => $event->uploaded(),
                ],
                accountId: $recipient->accountId(),
            ));
        }
    }

    /**
     * @return array<string, User>
     */
    private function recipients(): array
    {
        $recipients = [];

        foreach ([Role::Admin, Role::SuperAdmin] as $role) {
            foreach ($this->userRepository->findByFilter(new UserFilter(role: $role, limit: 0)) as $user) {
                $recipients[(string) $user->id()] = $user;
            }
        }

        return $recipients;
    }
}
