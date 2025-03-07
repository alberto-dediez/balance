<?php

namespace App\Security\Infrastructure\UI\Web\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\RegisterUserType;
use App\Security\Infrastructure\UI\Web\User\RegisterUserTrait;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/register', name: 'users_register')]
#[IsGranted("ROLE_OWNER")]
class RegisterController extends AbstractController
{
    use RegisterUserTrait, RegisterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        Request $request
    ): Response
    {   
        $form = $this->createForm(RegisterUserType::class);

        $needsRedirect = $this->register($request, $form, function ($form) use ($loggedAccount) {
            return $this->registerUser(
                form: $form,
                account: $loggedAccount()
            );
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('users');
        }

        return $this->render('@security/users/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
