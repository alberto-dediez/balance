<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

class CurrentRoles implements CurrentRolesProvider
{
    public function __construct(
        private readonly ImpersonatedAccount $impersonatedAccount,
        private readonly LoggedAccount $loggedAccount,
        private readonly LoggedUser $loggedUser,
    ) {}

    public function currentRoles(User $user): ?array
    {
        ($this->loggedAccount)();

        if (!$this->impersonatedAccount->isImpersonating()) {
            return null;
        }

        $impersonatorId = $this->impersonatedAccount->impersonatorId();

        if (!$impersonatorId instanceof UserId || !$impersonatorId->equals($user->id())) {
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
