<?php

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\UI\Web\User\RegisterUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\Symfony\Form\AdminRegisterUserType;

#[Route('/admin/users/register', name: 'admin_users_register')]
#[IsGranted("ROLE_ADMIN")]
class RegisterController extends AbstractController
{
    use RegisterUserTrait, RegisterTrait;

    public function __invoke(
        Request $request
    ): Response
    {   
        $form = $this->createForm(AdminRegisterUserType::class);

        $needsRedirect = $this->register($request, $form, function ($form) {
            return $this->registerUser(
                form: $form,
                account: $form->get('account')->getData()
            );
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('@security/admin/users/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
