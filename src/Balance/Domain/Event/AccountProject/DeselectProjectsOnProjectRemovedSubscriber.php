<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class DeselectProjectsOnProjectRemovedSubscriber implements DomainEventSubscriber
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

        assert($event instanceof ProjectRemoved);
        $this->handleProjectRemoved($event);
    }

    private function handleProjectRemoved(ProjectRemoved $event): void
    {
        $accountProjects = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(projectId: $event->projectId(), limit: 0),
        );

        foreach ($accountProjects as $accountProject) {
            $accountProject->deselect();
            $this->accountProjectRepository->remove($accountProject);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectRemoved;
    }
}
