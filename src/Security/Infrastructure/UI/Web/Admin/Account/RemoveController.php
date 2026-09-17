<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Account\Remove\RemoveAccountRequest;
use App\Security\Application\Account\Remove\RemoveAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/accounts/remove/{accountId}', name: 'admin_accounts_remove')]
#[CsrfProtected('remove')]
#[IsGranted('ROLE_ADMIN')]
class RemoveController extends AbstractController
{
    use GetAccountFromIdTrait;

    public function __invoke(
        string $accountId,
        LoggedUser $loggedUser,
        AccountRepository $accountRepository,
        RemoveAccountService $removeAccount,
    ): Response {
        $account = $this->getAccountFromId($accountId, $accountRepository);

        try {
            $removeAccount(new RemoveAccountRequest($account->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la cuenta {account}', ['{account}' => $account->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la cuenta {account}', ['{account}' => $account->name()]));
        }

        return $this->redirectToRoute('admin_accounts');
    }
}
