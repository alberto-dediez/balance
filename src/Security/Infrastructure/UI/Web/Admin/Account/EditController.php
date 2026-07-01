<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Account\Update\UpdateAccountRequest;
use App\Security\Application\Account\Update\UpdateAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Form\UpdateAccountType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/accounts/edit/{accountId}', name: 'admin_accounts_edit')]
#[IsGranted('ROLE_ADMIN')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetAccountFromIdTrait;

    public function __invoke(
        string $accountId,
        AccountRepository $accountRepository,
        LoggedUser $loggedUser,
        UpdateAccountService $updateAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $account = $this->getAccountFromId($accountId, $accountRepository);

        $updateRequest = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            emissionFactorGroupId: $account->emissionFactorGroupId(),
        );

        $form = $this->createForm(UpdateAccountType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateAccount, $user): void {
                $accountRequest = $form->getData();

                $updateAccount($accountRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Cuenta actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_accounts');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/accounts/edit.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }
}
