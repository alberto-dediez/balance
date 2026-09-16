<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

trait EditUserTrait
{
    public function __construct(
        private GrantRoleService $grantRole,
        private UngrantRoleService $ungrantRole,
    ) {}

    /**
     * @param Role[] $roles
     * @param Role[] $manageableRoles
     */
    protected function updateRoles(User $user, array $roles, array $manageableRoles, User $performBy): void
    {
        $this->ungrantRoles($user, $roles, $manageableRoles, $performBy);
        $this->grantRoles($user, $roles, $performBy);
    }

    /**
     * @param Role[] $roles
     */
    protected function grantRoles(User $user, array $roles, User $performBy): void
    {
        if (0 === count($roles)) {
            return;
        }

        $grantRole = $this->grantRole;

        foreach ($roles as $role) {
            $userHasRole = in_array($role, $user->roles());

            if ($userHasRole) {
                continue;
            }

            $request = new GrantRoleRequest($user->id(), $role);

            try {
                $grantRole($request, $performBy);

                $this->addFlash('success', new TranslatableMessage('Concedido acceso como {role}', ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido conceder acceso como {role}', ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }

    /**
     * @param Role[] $roles
     * @param Role[] $manageableRoles
     */
    protected function ungrantRoles(User $user, array $roles, array $manageableRoles, User $performBy): void
    {
        if (0 === count($roles)) {
            return;
        }

        $ungrantRole = $this->ungrantRole;

        foreach ($user->roles() as $role) {
            if (Role::User === $role) {
                continue;
            }

            if (!in_array($role, $manageableRoles, true)) {
                continue;
            }

            $roleIsAllowed = in_array($role, $roles);

            if ($roleIsAllowed) {
                continue;
            }

            $request = new UngrantRoleRequest($user->id(), $role);

            try {
                $ungrantRole($request, $performBy);

                $this->addFlash('warning', new TranslatableMessage('Retirado acceso como {role}', ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido retirar acceso como {role}', ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }
}
