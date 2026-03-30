<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Form\EditUser;
use App\Security\Infrastructure\Symfony\Form\EditUserType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users/edit/{userId}', name: 'users_edit')]
#[IsGranted('ROLE_OWNER')]
class EditController extends AbstractController
{
    use EditUserTrait;
    use EditTrait;

    public function __invoke(
        string $userId,
        LoggedUser $loggedUser,
        UserRepository $userRepository,
        Request $request,
    ): Response {
        $user = $userRepository->find(new UserId($userId));

        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $performBy = $loggedUser();

        $this->denyAccessUnlessGranted(Action::Edit->value, $user);

        try {
            $form = $this->createForm(EditUserType::class, new EditUser($user->roles()));

            $needsRedirect = $this->edit($request, $form, function ($form) use ($user, $performBy): void {
                $this->updateRoles($user, $form->get('roles')->getData(), $performBy);
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('users');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
