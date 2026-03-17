<?php

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\ChangePassword\ChangePasswordRequest;
use App\Security\Application\User\ChangePassword\ChangePasswordService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\ProfileType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/profile', name: 'profile')]
#[IsGranted("ROLE_USER")]
class ProfileController extends AbstractController
{
    public function __invoke(
        LoggedUser $loggedUser,
        ChangePasswordService $changePassword,
        UserPasswordHasherInterface $passwordHasher,
        Request $request
    ): Response
    {   
        $user = $loggedUser();

        $form = $this->createForm(ProfileType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPasswordIsCorrect = $passwordHasher->isPasswordValid(new SecurityUser($user), $form->get('oldPassword')->getData());

            if (!$oldPasswordIsCorrect) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en el formulario. No se ha actualizado tu contraseña'));

                $form->get('oldPassword')->addError(new FormError('La contraseña antigua no es correcta.'));

                return $this->render('@security/profile.html.twig', [
                    'user' => $user,
                    'form' => $form->createView()
                ]);
            }

            try {
                $changePassword(new ChangePasswordRequest(
                    userId: $user->id(),
                    password: $form->get('password')->getData()
                ), $user);

                $this->addFlash('success', new TranslatableMessage('La contraseña se ha actualizado correctamente'));
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha actualizado tu contraseña'));
            }
        }

        return $this->render('@security/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    
}
