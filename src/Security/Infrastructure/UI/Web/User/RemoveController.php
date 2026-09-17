<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Security\Application\User\Remove\RemoveUserService;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/users/remove/{userId}', name: 'users_remove')]
#[CsrfProtected('remove')]
#[IsGranted('ROLE_OWNER')]
class RemoveController extends AbstractController
{
    public function __invoke(
        string $userId,
        UserRepository $userRepository,
        LoggedUser $loggedUser,
        RemoveUserService $removeUser,
    ): Response {
        $user = $userRepository->find(new UserId($userId));

        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $performBy = $loggedUser();

        try {
            $removeUser(new RemoveUserRequest($user->id()), $performBy);

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado al usuario {user}', ['{user}' => $user->username()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar al usuario {user}', ['{user}' => $user->username()]));
        }

        return $this->redirectToRoute('users');
    }
}
