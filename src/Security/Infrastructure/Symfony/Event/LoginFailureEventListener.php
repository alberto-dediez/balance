<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener]
class LoginFailureEventListener
{
    public function __invoke(LoginFailureEvent $event): void
    {
        if (!$event->getPassport() instanceof Passport) {
            return;
        }

        DomainEventPublisher::instance()
            ->publish(
                new FailureLoginAttempted(
                    username: $event->getPassport()->getBadge(UserBadge::class)?->getUserIdentifier() ?? '',
                    firewall: $event->getFirewallName(),
                ),
            )
        ;
    }
}
