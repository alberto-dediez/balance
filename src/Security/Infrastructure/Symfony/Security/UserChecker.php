<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof SecurityUser) {
            return;
        }

        if (!$user->isActive()) {
            throw new UserNotFoundException(sprintf('User with username %s not found', $user->username()));
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}
