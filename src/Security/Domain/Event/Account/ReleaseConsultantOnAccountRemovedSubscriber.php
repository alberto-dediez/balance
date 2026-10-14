<?php

declare(strict_types=1);

namespace App\Security\Domain\Event\Account;

use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReleaseConsultantOnAccountRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private AccountRepository $accountRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof AccountRemoved);
        $this->handleAccountRemoved($event);
    }

    private function handleAccountRemoved(AccountRemoved $event): void
    {
        $accounts = $this->accountRepository->findByFilter(
            new AccountFilter(consultantAccountId: $event->accountId(), limit: 0),
        );

        foreach ($accounts as $account) {
            $account->changeConsultant(null);
            $this->accountRepository->add($account);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof AccountRemoved;
    }
}
