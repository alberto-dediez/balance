<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Application\User\RecoverPassword\RecoverPasswordRequest;
use App\Security\Application\User\RecoverPassword\RecoverPasswordService;
use App\Security\Application\User\RecoverToken\RecoverTokenRequest;
use App\Security\Application\User\RecoverToken\RecoverTokenService;
use App\Security\Domain\Model\User\RecoverToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

class RecoverPasswordController extends AbstractController
{
    #[Route(path: '/recover-password', name: 'app_recover_password')]
    public function recoverToken(RecoverTokenService $recoverToken, Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED')) {
            return $this->redirectToRoute('home');
        }

        $form = $this->createFormBuilder()
            ->add('username', EmailType::class)
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $recoverToken(
                    new RecoverTokenRequest(
                        username: $form->get('username')->getData(),
                    ),
                );
            } catch (\InvalidArgumentException) {
                // No hacemos nada cuando el usuario no se encuentre
            }

            return $this->render('@security/password/thanks.html.twig');
        }

        return $this->render('@security/password/recover.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/recover-password/{token}', name: 'app_modify_password')]
    public function recoverPassword(string $token, RecoverPasswordService $recoverPassword, Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED')) {
            return $this->redirectToRoute('home');
        }

        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'first_options'  => [
                    'label' => new TranslatableMessage('Contraseña'),
                ],
                'second_options' => [
                    'label' => new TranslatableMessage('Repetir Contraseña'),
                ],
                'type' => PasswordType::class,
                'invalid_message' => new TranslatableMessage('Las contraseñas no coinciden.'),
                'required' => true,
            ])
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $recoverPassword(
                    new RecoverPasswordRequest(
                        token: new RecoverToken($token),
                        password: $form->get('password')->getData(),
                    ),
                );
            } catch (\InvalidArgumentException) {
                return $this->render('@security/password/error.html.twig');
            }

            return $this->render('@security/password/success.html.twig');
        }

        return $this->render('@security/password/modify.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
