<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;

trait EditUserTrait
{
    public function __construct(
        private GrantRoleService $grantRole, 
        private UngrantRoleService $ungrantRole
    ) {
        
    }

    protected function updateRoles(User $user, array $roles)
    {
        $this->ungrantRoles($user, $roles);
        $this->grantRoles($user, $roles);
    }

    protected function grantRoles(User $user, array $roles)
    {
        if (count($roles) == 0) {
            return ;
        }

        $grantRole = $this->grantRole;

        foreach ($roles as $role) {
            $userHasRole = false !== in_array($role, $user->roles());

            if ($userHasRole) {
                continue; 
            }

            $request = new GrantRoleRequest($user->id(), $role);

            try {
                $grantRole($request);

                $this->addFlash('success', new TranslatableMessage("Concedido acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("No se ha podido conceder acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }

    protected function ungrantRoles(User $user, array $roles)
    {
        if (count($roles) == 0) {
            return ;
        }

        $ungrantRole = $this->ungrantRole;

        foreach ($user->roles() as $role) {
            $roleIsAllowed = false !== in_array($role, $roles);

            if ($roleIsAllowed) {
                continue; 
            }

            $request = new UngrantRoleRequest($user->id(), $role);

            try {
                $ungrantRole($request);

                $this->addFlash('warning', new TranslatableMessage("Retirado acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("No se ha podido retirar acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }
}
