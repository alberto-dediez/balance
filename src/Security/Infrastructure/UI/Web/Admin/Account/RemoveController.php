<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\Account\Remove\RemoveAccountRequest;
use App\Security\Application\Account\Remove\RemoveAccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/accounts/remove/{accountId}', name: 'admin_accounts_remove')]
#[IsGranted("ROLE_ADMIN")]
class RemoveController extends AbstractController
{
    use GetAccountFromIdTrait;

    public function __invoke(
        string $accountId, 
        AccountRepository $accountRepository,
        RemoveAccountService $removeAccount
    ): Response
    {   
        $account = $this->getAccountFromId($accountId, $accountRepository);

        try {
            $removeAccount(new RemoveAccountRequest($account->id()));

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la cuenta {account}", ['{account}' => $account->name()]));
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la cuenta {account}", ['{account}' => $account->name()]));
        }

        return $this->redirectToRoute('admin_accounts');
    }
}
