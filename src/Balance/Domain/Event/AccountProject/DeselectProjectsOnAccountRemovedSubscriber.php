<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class DeselectProjectsOnAccountRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private AccountProjectRepository $accountProjectRepository,
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
        $accountProjects = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $event->accountId(), limit: 0),
        );

        foreach ($accountProjects as $accountProject) {
            $accountProject->deselect();
            $this->accountProjectRepository->remove($accountProject);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof AccountRemoved;
    }
}
