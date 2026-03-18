<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Security\Application\User\Remove\RemoveUserService;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\GetUserFromIdTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/users/remove/{userId}', name: 'admin_users_remove')]
#[IsGranted('ROLE_ADMIN')]
class RemoveController extends AbstractController
{
    use GetUserFromIdTrait;

    public function __invoke(
        string $userId,
        LoggedUser $loggedUser,
        UserRepository $userRepository,
        RemoveUserService $removeUser,
    ): Response {
        $user = $this->getUserFromId($userId, $userRepository);

        try {
            $removeUser(new RemoveUserRequest($user->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado al usuario {user}', ['{user}' => $user->username()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar al usuario {user}', ['{user}' => $user->username()]));
        }

        return $this->redirectToRoute('admin_users');
    }
}
