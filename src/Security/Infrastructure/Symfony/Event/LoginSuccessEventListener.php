<?php

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class LoginSuccessEventListener
{
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        DomainEventPublisher::instance()
            ->publish(
                new UserLogged(
                    userId: $user->id(),
                    firewall: $event->getFirewallName()
                )
            )
        ;
    }
}
