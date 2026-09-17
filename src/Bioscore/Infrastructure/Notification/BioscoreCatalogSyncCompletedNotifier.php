<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Notification;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogSyncCompleted;
use App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem\BioscoreCatalogRoutes;
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
use Symfony\Component\Translation\TranslatableMessage;

class BioscoreCatalogSyncCompletedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof BioscoreCatalogSyncCompleted);

        $params = [
            'newPending' => $event->newPending(),
            'invalidated' => $event->invalidated(),
            'failedCatalogs' => $event->failedCatalogs(),
            'newPendingCount' => count($event->newPending()),
            'invalidatedCount' => count($event->invalidated()),
            'routes' => BioscoreCatalogRoutes::all(),
        ];

        $subject = (string) new TranslatableMessage('Maestros de Bioscore pendientes de mapear');

        foreach ($this->administrators() as $admin) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@bioscore/notification/catalog-sync-completed.notification.html.twig',
                params: $params,
                accountId: $admin->accountId(),
            ));
        }
    }

    /** @return list<User> */
    private function administrators(): array
    {
        $superAdmins = $this->userRepository->findByFilter(new UserFilter(role: Role::SuperAdmin, excludeRole: null, limit: 0));
        $admins = $this->userRepository->findByFilter(new UserFilter(role: Role::Admin, excludeRole: null, limit: 0));

        return [...$superAdmins, ...$admins];
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof BioscoreCatalogSyncCompleted;
    }
}
