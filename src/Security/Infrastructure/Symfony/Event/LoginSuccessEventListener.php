<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
class LoginSuccessEventListener
{
    public function __invoke(LoginSuccessEvent $event): void
    {
        if ($event->getAuthenticatedToken() instanceof TwoFactorToken) {
            return;
        }

        $user = $event->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        DomainEventPublisher::instance()
            ->publish(
                new UserLogged(
                    userId: $user->id(),
                    firewall: $event->getFirewallName(),
                ),
            )
        ;
    }
}
