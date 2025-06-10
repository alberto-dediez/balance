<?php

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class LoginSuccessEventListener
{
    public function __invoke(LoginSuccessEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();

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