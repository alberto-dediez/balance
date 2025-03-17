<?php

namespace App\Security\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class LoginSuccessEventListener
{
    public function __invoke(LoginSuccessEvent $event)
    {
        DomainEventPublisher::instance()
            ->publish(
                new UserLogged(
                    userId: $event->getUser()->id(),
                    firewall: $event->getFirewallName()
                )
            )
        ;
    }
}