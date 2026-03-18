<?php

namespace App\Security\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

#[AsEventListener]
class LoginFailureEventListener
{
    public function __invoke(LoginFailureEvent $event): void
    {
        if (null === $event->getPassport()) {
            return ;
        }

        DomainEventPublisher::instance()
            ->publish(
                new FailureLoginAttempted(
                    username: $event->getPassport()->getBadge(UserBadge::class)?->getUserIdentifier() ?? '',
                    firewall: $event->getFirewallName()
                )
            )
        ;
    }
}
