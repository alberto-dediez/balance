<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Notification;

use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRemoved;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
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

class DistributorAssignmentRemovedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private AccountRepository $accountRepository,
        private UserRepository $userRepository,
        private PropertyRepository $propertyRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof DistributorAssignmentRemoved);
        $this->handleDistributorAssignmentRemoved($event);
    }

    private function handleDistributorAssignmentRemoved(DistributorAssignmentRemoved $event): void
    {
        if (!$event->notifyDistributor()) {
            return;
        }

        $distributor = $this->accountRepository->find($event->distributorAccountId());

        if (!$distributor instanceof Account) {
            return;
        }

        $propertyId = $event->propertyId();
        $property = $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId) : null;

        $params = [
            'accountName' => $this->accountName($event->accountId() ?? $property?->accountId()),
            'distributorName' => $distributor->name(),
            'propertyName' => $propertyId instanceof PropertyId ? ($property instanceof Property ? $property->name() : (string) $propertyId) : null,
        ];

        foreach ($this->owners($distributor) as $owner) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $owner->id(),
                channel: NotificationChannel::Internal,
                subject: (string) new TranslatableMessage('Se ha retirado tu acceso como distribuidor'),
                template: '@property/notification/distributor-assignment-removed.notification.html.twig',
                params: $params,
                accountId: $owner->accountId(),
            ));
        }
    }

    private function accountName(?AccountId $accountId): string
    {
        if (!$accountId instanceof AccountId) {
            return '';
        }

        return $this->accountRepository->find($accountId)?->name() ?? (string) $accountId;
    }

    /** @return list<User> */
    private function owners(Account $account): array
    {
        return $this->userRepository->findByFilter(
            new UserFilter(role: Role::Owner, account: $account, excludeRole: null, limit: 0),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof DistributorAssignmentRemoved;
    }
}
