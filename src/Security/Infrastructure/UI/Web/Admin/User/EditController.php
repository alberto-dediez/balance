<?php

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Security\Infrastructure\Symfony\Form\EditUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\UI\Web\User\EditUserTrait;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Form\AdminEditUserType;
use App\Security\Infrastructure\UI\Web\User\GetUserFromIdTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/users/edit/{userId}', name: 'admin_users_edit')]
#[IsGranted("ROLE_ADMIN")]
class EditController extends AbstractController
{
    use GetUserFromIdTrait, EditUserTrait, EditTrait;

    public function __invoke(
        string $userId, 
        LoggedUser $loggedUser, 
        UserRepository $userRepository, 
        Request $request
    ): Response
    {   
        $user = $this->getUserFromId($userId, $userRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $user);

        $performBy = $loggedUser();

        $form = $this->createForm(AdminEditUserType::class, new EditUser($user->roles()));

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($user, $performBy): void {
                $this->updateRoles($user, $form->get('roles')->getData(), $performBy);
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_users');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }
}
