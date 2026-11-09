<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseAssigned;
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

class CreditPurchaseAssignedNotifier implements DomainEventSubscriber
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

        assert($event instanceof CreditPurchaseAssigned);
        $this->handleCreditPurchaseAssigned($event);
    }

    private function handleCreditPurchaseAssigned(CreditPurchaseAssigned $event): void
    {
        $project = $this->projectRepository->find($event->projectId());

        $params = [
            'creditPurchaseId' => (string) $event->creditPurchaseId(),
            'purchasedTonnes' => $event->purchasedTonnes(),
            'reference' => $event->reference(),
            'projectName' => $project?->name() ?? (string) $event->projectId(),
            'projectMitecoId' => $project?->mitecoId(),
            'paymentType' => $event->paymentType()->value,
        ];

        $subject = (string) new TranslatableMessage('Se han asignado créditos de CO2 a su cuenta');

        foreach ($this->owners($event->accountId()) as $owner) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $owner->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@balance/notification/credit-purchase-assigned.notification.html.twig',
                params: $params,
                accountId: $owner->accountId(),
            ));
        }
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
        return $event instanceof CreditPurchaseAssigned;
    }
}
