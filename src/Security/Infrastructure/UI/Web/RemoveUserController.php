<?php

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\RemoveUser\RemoveUserRequest;
use App\Security\Application\RemoveUser\RemoveUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/users/remove/{userId}', name: 'admin_users_remove')]
#[IsGranted("ROLE_ADMIN")]
class RemoveUserController extends AbstractController
{
    public function __construct(private RemoveUserService $removeUser)
    {

    }

    public function __invoke(string $userId, UserRepository $userRepository): Response
    {   
        $user = $userRepository->find(new UserId($userId));

        if (null == $user) {
            throw $this->createNotFoundException(new TranslatableMessage("El usuario no existe"));
        }

        $removeUser = $this->removeUser;

        try {
            $removeUser(new RemoveUserRequest($user->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado al usuario {user}", ['{user}' => $user->username()]));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar al usuario {user}", ['{user}' => $user->username()]));
        }

        return $this->redirectToRoute('admin_users');
    }
}
