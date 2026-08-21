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
use App\Security\Domain\Model\Account\AccountId;
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

        foreach ($this->owners($event->accountId()) as $owner) {
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
    }

    private function subjectFor(CreditAlertThreshold $threshold): string
    {
        return (string) match ($threshold) {
            CreditAlertThreshold::Warning => new TranslatableMessage('Le queda poco crédito en el proyecto {project}'),
            CreditAlertThreshold::High => new TranslatableMessage('Su crédito en el proyecto {project} está casi agotado'),
            CreditAlertThreshold::Depleted => new TranslatableMessage('Se ha quedado sin crédito en el proyecto {project}'),
        };
    }

    /** @return list<User> */
    private function owners(AccountId $accountId): array
    {
        $account = $this->accountRepository->find($accountId);

        if (!$account instanceof Account) {
            return [];
        }

        return $this->userRepository->findByFilter(
            new UserFilter(role: Role::Owner, account: $account, excludeRole: null, limit: 0),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CreditThresholdReached;
    }
}
