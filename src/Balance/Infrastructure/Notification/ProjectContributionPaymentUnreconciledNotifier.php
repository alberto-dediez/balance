<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaymentUnreconciled;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
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

class ProjectContributionPaymentUnreconciledNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private ProjectContributionRepository $projectContributionRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectContributionPaymentUnreconciled);
        $this->handleProjectContributionPaymentUnreconciled($event);
    }

    private function handleProjectContributionPaymentUnreconciled(ProjectContributionPaymentUnreconciled $event): void
    {
        $projectContribution = $this->projectContributionRepository->find($event->projectContributionId());

        $params = [
            'projectContributionId' => (string) $event->projectContributionId(),
            'status' => $event->status()->value,
            'paymentId' => null !== $event->paymentId() ? (string) $event->paymentId() : null,
            'totalAmount' => $projectContribution instanceof ProjectContribution ? $projectContribution->totalAmount() : null,
            'currency' => $projectContribution?->currency(),
            'contributorEmail' => $projectContribution?->contributorEmail(),
        ];

        $subject = (string) new TranslatableMessage('Pago recibido de una aportación ya cerrada');

        foreach ($this->administrators() as $admin) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@balance/notification/project-contribution-payment-unreconciled.notification.html.twig',
                params: $params,
                accountId: $admin->accountId(),
            ));

            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Email,
                subject: $subject,
                template: '@balance/notification/project-contribution-payment-unreconciled.email.html.twig',
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
        return $event instanceof ProjectContributionPaymentUnreconciled;
    }
}
