<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\RegisterAccount\RegisterAccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\Symfony\Form\RegisterAccountType;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/accounts/register', name: 'admin_accounts_register')]
#[IsGranted("ROLE_ADMIN")]
class RegisterAccountController extends AbstractController
{
    public function __construct(private RegisterAccountService $registerAccount)
    {

    }

    public function __invoke(Request $request): Response
    {   
        $form = $this->createForm(RegisterAccountType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registerRequest = $form->getData();

            $registerAccount = $this->registerAccount;

            try {
                $response = $registerAccount($registerRequest);

                $this->addFlash('success', new TranslatableMessage("Cuenta creada correctamente: {name}", ['{name}' => $response->name]));

                return $this->redirectToRoute('admin_accounts');
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage("Ha ocurrido un error inesperado al intentar registrar la cuenta."));
            }
        }

        return $this->render('@security/accounts/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
