<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;

class IntegrationUserProvider
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly string $username,
    ) {}

    public function user(): User
    {
        if ('' === trim($this->username)) {
            throw IntegrationUserNotAvailable::because('INTEGRATION_USERNAME is empty');
        }

        $user = $this->userRepository->findByUsername($this->username);

        if (!$user instanceof User) {
            throw IntegrationUserNotAvailable::because(sprintf('user "%s" not found', $this->username));
        }

        if (!$user->hasRole(Role::Admin) && !$user->hasRole(Role::SuperAdmin)) {
            throw IntegrationUserNotAvailable::because(sprintf('user "%s" is not an administrator', $this->username));
        }

        return $user;
    }
}
