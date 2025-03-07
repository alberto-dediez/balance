<?php

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Form\EditUser;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\AdminEditUserType;

#[Route('/admin/users/edit/{userId}', name: 'admin_users_edit')]
#[IsGranted("ROLE_ADMIN")]
class AdminEditUserController extends EditUserController
{
    public function __invoke(string $userId, UserRepository $userRepository, Request $request): Response
    {   
        $user = $userRepository->find(new UserId($userId));

        if (null == $user) {
            throw $this->createNotFoundException(new TranslatableMessage("El usuario no existe"));
        }

        $form = $this->createForm(AdminEditUserType::class, new EditUser($user->roles()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateRoles($user, $form->get('roles')->getData());

            if ($form->getClickedButton() && 'saveAndReturn' == $form->getClickedButton()->getName()) {
                return $this->redirectToRoute('admin_users');
            }
        }

        return $this->render('@security/admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }
}
