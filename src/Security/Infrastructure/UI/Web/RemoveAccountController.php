<?php

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\RemoveAccount\RemoveAccountRequest;
use App\Security\Application\RemoveAccount\RemoveAccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/accounts/remove/{accountId}', name: 'admin_accounts_remove')]
#[IsGranted("ROLE_ADMIN")]
class RemoveAccountController extends AbstractController
{
    public function __construct(private RemoveAccountService $removeAccount)
    {

    }

    public function __invoke(string $accountId, AccountRepository $accountRepository): Response
    {   
        $account = $accountRepository->find(new AccountId($accountId));

        if (null == $account) {
            throw $this->createNotFoundException(new TranslatableMessage("La cuenta no existe"));
        }

        $removeAccount = $this->removeAccount;

        try {
            $removeAccount(new RemoveAccountRequest($account->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la cuenta {account}", ['{account}' => $account->name()]));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la cuenta {account}", ['{account}' => $account->name()]));
        }

        return $this->redirectToRoute('admin_accounts');
    }
}
