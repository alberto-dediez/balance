<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

class CurrentRoles implements CurrentRolesProvider
{
    public function __construct(
        private readonly CurrentImpersonation $currentImpersonation,
        private readonly LoggedUser $loggedUser,
    ) {}

    public function currentRoles(User $user): ?array
    {
        if (!$this->currentImpersonation->isImpersonating($user)) {
            return null;
        }

        return [Role::Owner, Role::User];
    }

    public function currentLoggedUserRoles(): ?array
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return null;
        }

        return $this->currentRoles($user);
    }
}
