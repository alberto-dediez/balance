<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Event\Employee;

use App\HumanResources\Application\Employee\UnlinkUser\UnlinkUserRequest;
use App\HumanResources\Application\Employee\UnlinkUser\UnlinkUserService;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class UnlinkEmployeeOnUserRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmployeeRepository $employeeRepository,
        private UnlinkUserService $unlinkUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof UserRemoved);
        $this->handleUserRemoved($event);
    }

    private function handleUserRemoved(UserRemoved $event): void
    {
        $employees = $this->employeeRepository->findByFilter(
            new EmployeeFilter(userId: $event->userId(), limit: 1),
        );

        if (0 === count($employees)) {
            return;
        }

        $unlinkUser = $this->unlinkUser;
        $unlinkUser(new UnlinkUserRequest($employees[0]->id()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof UserRemoved;
    }
}
