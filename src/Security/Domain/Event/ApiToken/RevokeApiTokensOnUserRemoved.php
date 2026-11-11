<?php

declare(strict_types=1);

namespace App\Security\Domain\Event\ApiToken;

use App\Security\Application\ApiToken\RevokeAll\RevokeApiTokensOfRemovedUserRequest;
use App\Security\Application\ApiToken\RevokeAll\RevokeApiTokensOfRemovedUserService;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class RevokeApiTokensOnUserRemoved implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RevokeApiTokensOfRemovedUserService $revokeApiTokensOfRemovedUser,
        private LoggedUser $loggedUser,
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
        $performBy = $this->loggedUser->getLoggedUserOrNull() ?? UserMother::systemUser();

        $revokeApiTokensOfRemovedUser = $this->revokeApiTokensOfRemovedUser;

        $revokeApiTokensOfRemovedUser(new RevokeApiTokensOfRemovedUserRequest($event->userId()), $performBy);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof UserRemoved;
    }
}
