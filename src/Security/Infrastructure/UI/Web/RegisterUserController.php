<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Component\Form\FormError;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\GrantRole\GrantRoleRequest;
use App\Security\Application\GrantRole\GrantRoleService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\RegisterUser\RegisterUserRequest;
use App\Security\Application\RegisterUser\RegisterUserService;
use App\Security\Infrastructure\Symfony\Form\RegisterUserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Application\RegisterUser\DuplicateUsernameException;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/users/register', name: 'users_register')]
#[IsGranted("ROLE_OWNER")]
class RegisterUserController extends AbstractController
{
    private ?Account $account = null;

    public function __construct(
        private RegisterUserService $registerUser, 
        private GrantRoleService $grantRole,
        Security $security
    ) {
        $this->account = new Account($security->getUser()->accountId());
    }

    public function __invoke(Request $request): Response
    {   
        $form = $this->createForm(RegisterUserType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registerRequest = new RegisterUserRequest(
                $form->get('username')->getData(),
                $form->get('password')->getData(),
                null !== $this->account ? $this->account->id() : null
            );

            $registerUser = $this->registerUser;

            try {
                $response = $registerUser($registerRequest);

                $this->addFlash('success', new TranslatableMessage("Usuario creado correctamente: {username}", ['{username}' => $response->username]));

                $this->grantRoles($response->userId, $form->get('roles')->getData());

                return $this->redirectToRoute('users');
            } catch (DuplicateUsernameException $e) {
                $form->get('username')->addError(new FormError('El nombre de usuario ya existe'));

                $this->addFlash('danger', new TranslatableMessage("El formulario contiene errores, por favor, reviselos antes de continuar."));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("Ha ocurrido un error inesperado al intentar registrar al usuario."));
            }
        }

        return $this->render('@security/users/register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function grantRoles(UserId $userId, array $roles)
    {
        if (count($roles) == 0) {
            return ;
        }

        $grantRole = $this->grantRole;

        foreach ($roles as $role) {
            $request = new GrantRoleRequest($userId, $role);

            try {
                $grantRole($request);

                $this->addFlash('success', new TranslatableMessage("Concedido acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("No se ha podido conceder acceso como {role}", ['{role}' => new TranslatableMessage($role->value, [], 'roles')]));
            }
        }
    }
}
