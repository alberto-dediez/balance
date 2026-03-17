<?php

namespace App\Security\Domain\Event\User;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Security\Application\User\Remove\RemoveUserService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

class RemoveAccountSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private UserRepository $userRepository, private RemoveUserService $removeUser, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event)
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        assert($event instanceof AccountRemoved);

        $this->handleAccountRemoved($event);
    }

    private function handleAccountRemoved(AccountRemoved $event)
    {
        $users = $this->userRepository->findByAccountId($event->accountId());

        if (count($users) == 0) {
            return ;
        }

        $performBy = $this->loggedUser->__invoke();

        $removeUser = $this->removeUser;

        foreach ($users as $user) {
            $request = new RemoveUserRequest(
                $user->id()
            );

            $removeUser($request, $performBy);
        }
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return $event instanceof AccountRemoved;
    }
}
