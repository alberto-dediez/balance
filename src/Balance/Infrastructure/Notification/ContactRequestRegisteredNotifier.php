<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\ContactRequest\ContactRequestRegistered;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class ContactRequestRegisteredNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private AccountRepository $accountRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ContactRequestRegistered);
        $this->handleContactRequestRegistered($event);
    }

    private function handleContactRequestRegistered(ContactRequestRegistered $event): void
    {
        $accountName = $this->accountRepository->find($event->accountId())?->name() ?? (string) $event->accountId();

        $params = [
            'contactRequestId' => (string) $event->contactRequestId(),
            'accountName' => $accountName,
            'contactName' => $event->contactName(),
            'contactEmail' => $event->contactEmail(),
            'contactPhone' => $event->contactPhone(),
            'originName' => $event->originName(),
            'co2eqKg' => $event->co2eqKg(),
        ];

        $subject = (string) new TranslatableMessage('Nueva solicitud de contacto para compensar de la cuenta {account}');
        $subjectParams = ['{account}' => $accountName];

        foreach ($this->administrators() as $admin) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@balance/notification/contact-request-registered.notification.html.twig',
                params: $params,
                accountId: $admin->accountId(),
                subjectParams: $subjectParams,
            ));

            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Email,
                subject: $subject,
                template: '@balance/notification/contact-request-registered.email.html.twig',
                params: $params,
                accountId: $admin->accountId(),
                subjectParams: $subjectParams,
            ));
        }
    }

    /** @return list<User> */
    private function administrators(): array
    {
        return $this->userRepository->findByFilter(
            new UserFilter(role: Role::Admin, excludeRole: Role::SuperAdmin, limit: 0),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ContactRequestRegistered;
    }
}
