<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\User\DuplicatedUsernameException;
use Symfony\Component\Form\FormError;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;

trait RegisterUserTrait
{
    public function __construct(
        private RegisterUserService $registerUser,
        private GrantRoleService $grantRole
    ) {

    }

    private function registerUser($form, ?Account $account, User $performBy)
    {
        $registerRequest = new RegisterUserRequest(
            $form->get('username')->getData(),
            $form->get('password')->getData(),
            null !== $account ? $account->id() : null
        );

        $registerUser = $this->registerUser;

        try {
            $response = $registerUser($registerRequest, $performBy);

            $this->addFlash('success', new TranslatableMessage("Usuario creado correctamente: {username}", ['{username}' => $response->username]));

            $this->grantRoles($response->userId, $form->get('roles')->getData(), $performBy);

            return true;
        } catch (DuplicatedUsernameException $e) {
            $form->get('username')->addError(new FormError('El nombre de usuario ya existe'));

            $this->addFlash('danger', new TranslatableMessage("El formulario contiene errores, por favor, reviselos antes de continuar."));
        }

        return false;
    }

    private function grantRoles(UserId $userId, array $roles, User $performBy)
    {
        if (count($roles) == 0) {
            return ;
        }

        $grantRole = $this->grantRole;

        foreach ($roles as $role) {
            $request = new GrantRoleRequest($userId, $role);

            try {
                $grantRole($request, $performBy);

                $this->addFlash('success', new TranslatableMessage("Concedido acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("No se ha podido conceder acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }
}
