<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Component\Form\FormError;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\GrantRole\GrantRoleRequest;
use App\Security\Application\GrantRole\GrantRoleService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\RegisterUser\RegisterUserRequest;
use App\Security\Application\RegisterUser\RegisterUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\Symfony\Form\AdminRegisterUserType;
use App\Security\Application\RegisterUser\DuplicateUsernameException;

#[Route('/admin/users/register', name: 'admin_users_register')]
#[IsGranted("ROLE_ADMIN")]
class AdminRegisterUserController extends AbstractController
{
    public function __construct(private RegisterUserService $registerUser, private GrantRoleService $grantRole)
    {

    }

    public function __invoke(Request $request): Response
    {   
        $form = $this->createForm(AdminRegisterUserType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $account = $form->get('account')->getData();

            $registerRequest = new RegisterUserRequest(
                $form->get('username')->getData(),
                $form->get('password')->getData(),
                null !== $account ? $account->id() : null
            );

            $registerUser = $this->registerUser;

            try {
                $response = $registerUser($registerRequest);

                $this->addFlash('success', new TranslatableMessage("Usuario creado correctamente: {username}", ['{username}' => $response->username]));

                $this->grantRoles($response->userId, $form->get('roles')->getData());

                return $this->redirectToRoute('admin_users');
            } catch (DuplicateUsernameException $e) {
                $form->get('username')->addError(new FormError('El nombre de usuario ya existe'));

                $this->addFlash('danger', new TranslatableMessage("El formulario contiene errores, por favor, reviselos antes de continuar."));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("Ha ocurrido un error inesperado al intentar registrar al usuario."));
            }
        }

        return $this->render('@security/admin/users/register.html.twig', [
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
