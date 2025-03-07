<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Form\EditUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\EditUserType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/edit/{userId}', name: 'users_edit')]
#[IsGranted("ROLE_OWNER")]
class EditController extends AbstractController
{
    use OnlyUsersFromAccountTrait, EditUserTrait, EditTrait;

    public function __invoke(
        string $userId, 
        UserRepository $userRepository, 
        LoggedAccount $loggedAccount,
        Request $request
    ): Response
    {   
        $user = $userRepository->find(new UserId($userId));
        $account = $loggedAccount();

        $this->onlyUsersFromAccount($user, $account);

        $form = $this->createForm(EditUserType::class, new EditUser($user->roles()));

        $needsRedirect = $this->edit($request, $form, function ($form) use ($user) {
            $this->updateRoles($user, $form->get('roles')->getData());
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('users');
        }

        return $this->render('@security/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    
}
