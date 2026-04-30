<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Event\Employee;

use App\HumanResources\Application\Employee\LinkUser\LinkUserRequest;
use App\HumanResources\Application\Employee\LinkUser\LinkUserService;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRegistered;
use App\HumanResources\Domain\Model\Employee\EmployeeUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class LinkUserOnEmployeeEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private UserRepository $userRepository,
        private LinkUserService $linkUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        if ($event instanceof EmployeeRegistered) {
            $this->handleEmployeeRegistered($event);
        } elseif ($event instanceof EmployeeUpdated) {
            $this->handleEmployeeUpdated($event);
        }
    }

    private function handleEmployeeRegistered(EmployeeRegistered $event): void
    {
        if (null !== $event->userId()) {
            return;
        }

        $this->tryLinkUser($event->employeeId(), $event->email(), $event->accountId());
    }

    private function handleEmployeeUpdated(EmployeeUpdated $event): void
    {
        if (null !== $event->userId()) {
            return;
        }

        $this->tryLinkUser($event->employeeId(), $event->email(), $event->accountId());
    }

    private function tryLinkUser(EmployeeId $employeeId, string $email, AccountId $accountId): void
    {
        $user = $this->userRepository->findByUsername($email);

        if (null === $user) {
            return;
        }

        if (null === $user->accountId() || !$user->accountId()->equals($accountId)) {
            return;
        }

        $linkUser = $this->linkUser;
        $linkUser(new LinkUserRequest($employeeId, $user->id()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EmployeeRegistered || $event instanceof EmployeeUpdated;
    }
}
