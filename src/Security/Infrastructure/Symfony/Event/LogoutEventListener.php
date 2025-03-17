<?php

namespace App\Security\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

#[AsEventListener]
class LogoutEventListener
{
    public function __invoke(LogoutEvent $event)
    {
        DomainEventPublisher::instance()
            ->publish(
                new UserLoggedOut(
                    userId: $event->getToken()->getUser()->id()
                )
            )
        ;
    }
}