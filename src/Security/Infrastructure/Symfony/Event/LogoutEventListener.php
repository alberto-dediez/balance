<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener]
class LogoutEventListener
{
    public function __invoke(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        DomainEventPublisher::instance()
            ->publish(
                new UserLoggedOut(
                    userId: $user->id(),
                ),
            )
        ;
    }
}
