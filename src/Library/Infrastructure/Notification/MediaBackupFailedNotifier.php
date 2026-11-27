<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Notification;

use App\Library\Domain\Model\Backup\MediaBackupFailed;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class MediaBackupFailedNotifier implements DomainEventSubscriber
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
        if (!$event instanceof MediaBackupFailed) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->handleMediaBackupFailed($event);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MediaBackupFailed;
    }

    private function handleMediaBackupFailed(MediaBackupFailed $event): void
    {
        foreach ($this->userRepository->findByFilter(new UserFilter(role: Role::SuperAdmin, limit: 0)) as $recipient) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $recipient->id(),
                channel: NotificationChannel::Internal,
                subject: 'La copia de seguridad de la biblioteca ha fallado',
                template: '@library/backup/failed.notification.html.twig',
                params: [
                    'failed' => $event->failed(),
                    'error' => $event->error(),
                ],
                accountId: $recipient->accountId(),
            ));
        }
    }
}
