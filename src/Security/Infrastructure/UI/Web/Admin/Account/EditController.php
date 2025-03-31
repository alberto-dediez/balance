<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\UpdateAccountType;
use App\Security\Application\Account\Update\UpdateAccountRequest;
use App\Security\Application\Account\Update\UpdateAccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\UI\Web\Admin\Account\GetAccountFromIdTrait;

#[Route('/admin/accounts/edit/{accountId}', name: 'admin_accounts_edit')]
#[IsGranted("ROLE_ADMIN")]
class EditController extends AbstractController
{
    use EditTrait, GetAccountFromIdTrait;

    public function __invoke(
        string $accountId, 
        AccountRepository $accountRepository, 
        UpdateAccountService $updateAccount, 
        Request $request
    ): Response
    {   
        $account = $this->getAccountFromId($accountId, $accountRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $account);

        $updateRequest = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive()
        );

        $form = $this->createForm(UpdateAccountType::class, $updateRequest); 

        $needsRedirect = $this->edit($request, $form, function ($form) use ($updateAccount) {
            $accountRequest = $form->getData();

            $updateAccount($accountRequest);

            $this->addFlash('success', new TranslatableMessage('Cuenta actualizada con éxito'));
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_accounts');
        }

        return $this->render('@security/admin/accounts/edit.html.twig', [
            'account' => $account,
            'form' => $form->createView()
        ]);
    }
}
