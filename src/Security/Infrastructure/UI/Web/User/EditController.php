<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Security\Infrastructure\Symfony\Form\EditUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Form\EditUserType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/edit/{userId}', name: 'users_edit')]
#[IsGranted("ROLE_OWNER")]
class EditController extends AbstractController
{
    use EditUserTrait, EditTrait;

    public function __invoke(
        string $userId, 
        LoggedUser $loggedUser,
        UserRepository $userRepository, 
        Request $request
    ): Response
    {   
        $user = $userRepository->find(new UserId($userId));

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
            'form' => $form->createView()
        ]);
    }

    
}
