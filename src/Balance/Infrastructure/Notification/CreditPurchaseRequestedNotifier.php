<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRequested;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class CreditPurchaseRequestedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private ProjectRepository $projectRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CreditPurchaseRequested);
        $this->handleCreditPurchaseRequested($event);
    }

    private function handleCreditPurchaseRequested(CreditPurchaseRequested $event): void
    {
        $project = $this->projectRepository->find($event->projectId());

        $params = [
            'creditPurchaseId' => (string) $event->creditPurchaseId(),
            'requestedTonnes' => $event->requestedTonnes(),
            'projectName' => $project?->name(),
            'projectMitecoId' => $project?->mitecoId(),
        ];

        foreach ($this->administrators() as $admin) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Internal,
                subject: (string) new TranslatableMessage('Nueva solicitud de créditos de CO2'),
                template: '@balance/notification/credit-purchase-requested.notification.html.twig',
                params: $params,
                accountId: $admin->accountId(),
            ));

            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Email,
                subject: (string) new TranslatableMessage('Nueva solicitud de créditos de CO2'),
                template: '@balance/notification/credit-purchase-requested.email.html.twig',
                params: $params,
                accountId: $admin->accountId(),
            ));
        }
    }

    /** @return list<\App\Security\Domain\Model\User\User> */
    private function administrators(): array
    {
        $superAdmins = $this->userRepository->findByFilter(new UserFilter(role: Role::SuperAdmin, excludeRole: null, limit: 0));
        $admins = $this->userRepository->findByFilter(new UserFilter(role: Role::Admin, excludeRole: null, limit: 0));

        return [...$superAdmins, ...$admins];
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CreditPurchaseRequested;
    }
}
