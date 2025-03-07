<?php

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Form\EditUser;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\GrantRole\GrantRoleRequest;
use App\Security\Application\GrantRole\GrantRoleService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\EditUserType;
use App\Security\Application\UngrantRole\UngrantRoleRequest;
use App\Security\Application\UngrantRole\UngrantRoleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/edit/{userId}', name: 'users_edit')]
#[IsGranted("ROLE_OWNER")]
class EditUserController extends AbstractController
{
    private ?Account $account = null;

    public function __construct(
        private GrantRoleService $grantRole, 
        private UngrantRoleService $ungrantRole,
        Security $security
    ) {
        $this->account = null !== $security->getUser()->accountId()
            ? new Account($security->getUser()->accountId())
            : null
        ;
    }

    public function __invoke(string $userId, UserRepository $userRepository, Request $request): Response
    {   
        $user = $userRepository->find(new UserId($userId));

        if (null == $user || null == $user->accountId() || false == $user->accountId()->equals($this->account->id())) {
            throw $this->createNotFoundException(new TranslatableMessage("El usuario no existe"));
        }

        $form = $this->createForm(EditUserType::class, new EditUser($user->roles()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateRoles($user, $form->get('roles')->getData());

            if ($form->getClickedButton() && 'saveAndReturn' == $form->getClickedButton()->getName()) {
                return $this->redirectToRoute('users');
            }
        }

        return $this->render('@security/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
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
