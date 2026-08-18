<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\LedgerEntry\LedgerCheckCompleted;
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

class LedgerCheckCompletedNotifier implements DomainEventSubscriber
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

        assert($event instanceof LedgerCheckCompleted);
        $this->handleLedgerCheckCompleted($event);
    }

    private function handleLedgerCheckCompleted(LedgerCheckCompleted $event): void
    {
        $params = [
            'findings' => $event->findings(),
            'findingsCount' => count($event->findings()),
            'checkedContributions' => $event->checkedContributions(),
            'from' => $event->from(),
            'to' => $event->to(),
        ];

        $subject = (string) new TranslatableMessage('Incidencias en el libro mayor');

        foreach ($this->administrators() as $admin) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@balance/notification/ledger-check-completed.notification.html.twig',
                params: $params,
                accountId: $admin->accountId(),
            ));

            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Email,
                subject: $subject,
                template: '@balance/notification/ledger-check-completed.email.html.twig',
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
        return $event instanceof LedgerCheckCompleted;
    }
}
