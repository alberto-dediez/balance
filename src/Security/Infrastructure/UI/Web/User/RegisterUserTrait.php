<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\UpdateProfile\UpdateProfileRequest;
use App\Security\Application\User\UpdateProfile\UpdateProfileService;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\DuplicatedUsernameException;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatableMessage;

trait RegisterUserTrait
{
    public function __construct(
        private RegisterUserService $registerUser,
        private GrantRoleService $grantRole,
        private UpdateProfileService $updateProfile,
    ) {}

    /**
     * @param FormInterface<mixed> $form
     */
    private function registerUser(FormInterface $form, ?Account $account, User $performBy, ?Locale $preferredLocale = null): bool
    {
        $registerRequest = new RegisterUserRequest(
            $form->get('username')->getData(),
            $form->get('password')->getData(),
            $account instanceof Account ? $account->id() : null,
        );

        $registerUser = $this->registerUser;

        try {
            $response = $registerUser($registerRequest, $performBy);

            $this->addFlash('success', new TranslatableMessage('Usuario creado correctamente: {username}', ['{username}' => $response->username]));

            $this->grantRoles($response->userId, $form->get('roles')->getData(), $performBy);

            if ($preferredLocale instanceof Locale) {
                $updateProfile = $this->updateProfile;
                $updateProfile(new UpdateProfileRequest(userId: $response->userId, preferredLocale: $preferredLocale), $performBy);
            }

            return true;
        } catch (DuplicatedUsernameException) {
            $form->get('username')->addError(new FormError('El nombre de usuario ya existe'));

            $this->addFlash('danger', new TranslatableMessage('El formulario contiene errores, por favor, reviselos antes de continuar.'));
        }

        return false;
    }

    /**
     * @param Role[] $roles
     */
    private function grantRoles(UserId $userId, array $roles, User $performBy): void
    {
        if (0 === count($roles)) {
            return;
        }

        $grantRole = $this->grantRole;

        foreach ($roles as $role) {
            $request = new GrantRoleRequest($userId, $role);

            try {
                $grantRole($request, $performBy);

                $this->addFlash('success', new TranslatableMessage('Concedido acceso como {role}', ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido conceder acceso como {role}', ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }
}
