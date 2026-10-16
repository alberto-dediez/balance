<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Event\Employee;

use App\HumanResources\Application\Employee\LinkUser\LinkUserRequest;
use App\HumanResources\Application\Employee\LinkUser\LinkUserService;
use App\HumanResources\Application\Employee\Register\RegisterEmployeeRequest;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRegistered;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LinkEmployeeOnUserRegisteredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmployeeRepository $employeeRepository,
        private UserRepository $userRepository,
        private LinkUserService $linkUser,
        private LoggedUser $loggedUser,
        #[Autowire(param: 'app.human_resources_enabled')]
        private bool $humanResourcesEnabled = true,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof UserRegistered);
        $this->handleUserRegistered($event);
    }

    private function handleUserRegistered(UserRegistered $event): void
    {
        if (!$this->humanResourcesEnabled) {
            return;
        }

        $accountId = $event->accountId();

        if (null === $accountId) {
            return;
        }

        $employees = $this->employeeRepository->findByFilter(
            new EmployeeFilter(
                email: $event->username(),
                accountId: $accountId,
                limit: 1,
            ),
        );

        if (0 === count($employees)) {
            $user = $this->loggedUser->getLoggedUserOrNull();

            if (!$user instanceof User) {
                $user = $this->userRepository->find($event->userId());
            }

            assert(null !== $user);

            CommandBusDispatcher::instance()->dispatch(
                new RegisterEmployeeRequest(
                    name: $event->username(),
                    email: $event->username(),
                    accountId: $accountId,
                    userId: $event->userId(),
                ),
                $user,
            );

            return;
        }

        $employee = $employees[0];

        if (null !== $employee->userId()) {
            return;
        }

        $linkUser = $this->linkUser;
        $linkUser(new LinkUserRequest($employee->id(), $event->userId()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof UserRegistered;
    }
}
