<?php

declare(strict_types=1);

namespace App\Security\Application\User\SystemActor;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;

class SystemActorResolver
{
    public const string SYSTEM_USERNAME = 'system';

    public function __construct(private readonly UserRepository $userRepository) {}

    public function __invoke(): User
    {
        return $this->resolve() ?? throw new SystemActorNotFoundException();
    }

    private function resolve(): ?User
    {
        $paymentUser = $this->userRepository->findByUsername(self::SYSTEM_USERNAME);

        if ($paymentUser instanceof User) {
            return $paymentUser;
        }

        return $this->firstWithRole(Role::Admin) ?? $this->firstWithRole(Role::SuperAdmin);
    }

    private function firstWithRole(Role $role): ?User
    {
        $users = $this->userRepository->findByFilter(new UserFilter(role: $role, excludeRole: null, limit: 1));

        return $users[0] ?? null;
    }
}
