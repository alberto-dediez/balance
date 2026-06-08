<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Application\User\UpdateProfile\UpdateProfileRequest;
use App\Security\Application\User\UpdateProfile\UpdateProfileService;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
class SetPreferredLocaleOnLoginListener
{
    public function __construct(private UpdateProfileService $updateProfile) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        if (null !== $user->preferredLocale()) {
            return;
        }

        if ('main' !== $event->getFirewallName()) {
            return;
        }

        ($this->updateProfile)(
            new UpdateProfileRequest(
                userId: $user->id(),
                preferredLocale: LocaleGetter::locale(),
            ),
            $user,
        );
    }
}
