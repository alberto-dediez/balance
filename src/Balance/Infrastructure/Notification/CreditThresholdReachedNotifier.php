<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditThresholdReached;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class CreditThresholdReachedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private ProjectRepository $projectRepository,
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

        assert($event instanceof CreditThresholdReached);
        $this->handleCreditThresholdReached($event);
    }

    private function handleCreditThresholdReached(CreditThresholdReached $event): void
    {
        $project = $this->projectRepository->find($event->projectId());
        $projectName = $project?->name() ?? (string) $event->projectId();

        $params = [
            'projectName' => $projectName,
            'projectMitecoId' => $project?->mitecoId(),
            'threshold' => $event->threshold()->value,
            'purchasedTonnes' => $event->purchasedTonnes(),
            'consumedTonnes' => $event->consumedTonnes(),
            'availableTonnes' => $event->availableTonnes(),
            'projectAvailableTonnes' => $event->projectAvailableTonnes(),
            'purchaseReference' => $event->purchaseReference(),
        ];

        $subject = $this->subjectFor($event->threshold());
        $subjectParams = ['{project}' => $projectName];

        $account = $this->accountRepository->find($event->accountId());
        $owners = $this->owners($account);

        foreach ($owners as $owner) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $owner->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@balance/notification/credit-threshold-reached.notification.html.twig',
                params: $params,
                accountId: $owner->accountId(),
                subjectParams: $subjectParams,
            ));

            $this->notifier->notify(new SystemNotification(
                recipientId: $owner->id(),
                channel: NotificationChannel::Email,
                subject: $subject,
                template: '@balance/notification/credit-threshold-reached.email.html.twig',
                params: $params,
                accountId: $owner->accountId(),
                subjectParams: $subjectParams,
            ));
        }

        $accountName = $account?->name() ?? (string) $event->accountId();
        $adminParams = [
            ...$params,
            'accountName' => $accountName,
            'creditPurchaseId' => (string) $event->creditPurchaseId(),
        ];
        $adminSubject = $this->adminSubjectFor($event->threshold());
        $adminSubjectParams = ['{account}' => $accountName, '{project}' => $projectName];

        foreach ($this->administratorsExcluding($owners) as $admin) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Internal,
                subject: $adminSubject,
                template: '@balance/notification/credit-threshold-reached-admin.notification.html.twig',
                params: $adminParams,
                accountId: $admin->accountId(),
                subjectParams: $adminSubjectParams,
            ));

            $this->notifier->notify(new SystemNotification(
                recipientId: $admin->id(),
                channel: NotificationChannel::Email,
                subject: $adminSubject,
                template: '@balance/notification/credit-threshold-reached-admin.email.html.twig',
                params: $adminParams,
                accountId: $admin->accountId(),
                subjectParams: $adminSubjectParams,
            ));
        }
    }

    private function subjectFor(CreditAlertThreshold $threshold): string
    {
        return (string) match ($threshold) {
            CreditAlertThreshold::Warning => new TranslatableMessage('Le queda poco crédito en el proyecto {project}'),
            CreditAlertThreshold::High => new TranslatableMessage('Su crédito en el proyecto {project} está casi agotado'),
            CreditAlertThreshold::Depleted => new TranslatableMessage('Se ha quedado sin crédito en el proyecto {project}'),
        };
    }

    private function adminSubjectFor(CreditAlertThreshold $threshold): string
    {
        return (string) match ($threshold) {
            CreditAlertThreshold::Warning => new TranslatableMessage('A la cuenta {account} le queda poco crédito en el proyecto {project}'),
            CreditAlertThreshold::High => new TranslatableMessage('El crédito de la cuenta {account} en el proyecto {project} está casi agotado'),
            CreditAlertThreshold::Depleted => new TranslatableMessage('La cuenta {account} se ha quedado sin crédito en el proyecto {project}'),
        };
    }

    /** @return list<User> */
    private function owners(?Account $account): array
    {
        if (!$account instanceof Account) {
            return [];
        }

        return $this->userRepository->findByFilter(
            new UserFilter(role: Role::Owner, account: $account, excludeRole: null, limit: 0),
        );
    }

    /**
     * @param list<User> $excluded
     *
     * @return list<User>
     */
    private function administratorsExcluding(array $excluded): array
    {
        $excludedIds = array_map(static fn(User $user): string => (string) $user->id(), $excluded);

        $administrators = $this->userRepository->findByFilter(
            new UserFilter(role: Role::Admin, excludeRole: Role::SuperAdmin, limit: 0),
        );

        return array_values(array_filter(
            $administrators,
            static fn(User $admin): bool => !in_array((string) $admin->id(), $excludedIds, true),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CreditThresholdReached;
    }
}
