<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\RegisterAccountType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Application\Account\Register\RegisterAccountService;

#[Route('/admin/accounts/register', name: 'admin_accounts_register')]
#[IsGranted("ROLE_ADMIN")]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterAccountService $registerAccount, Request $request): Response
    {   
        $account = new Account(AccountId::generate());

        $this->denyAccessUnlessGranted(Action::Register->value, $account);

        $form = $this->createForm(RegisterAccountType::class);

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerAccount) {
            $registerRequest = $form->getData();

            $response = $registerAccount($registerRequest);

            $this->addFlash('success', new TranslatableMessage("Cuenta creada correctamente: {name}", ['{name}' => $response->name]));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_accounts');
        }

        return $this->render('@security/admin/accounts/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
