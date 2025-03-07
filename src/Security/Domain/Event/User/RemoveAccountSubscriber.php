<?php

namespace App\Security\Domain\Event\User;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Security\Application\User\Remove\RemoveUserService;

class RemoveAccountSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private UserRepository $userRepository, private RemoveUserService $removeUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event)
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        $this->handleAccountRemoved($event);
    }

    private function handleAccountRemoved(AccountRemoved $event)
    {
        $users = $this->userRepository->findByAccountId($event->accountId());

        if (count($users) == 0) {
            return ;
        }

        $removeUser = $this->removeUser;

        foreach ($users as $user) {
            $request = new RemoveUserRequest(
                $user->id()
            );

            $removeUser($request);
        }
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return $event instanceof AccountRemoved;
    }
}
