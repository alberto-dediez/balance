<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\UpdateAccountType;
use App\Security\Application\UpdateAccount\UpdateAccountRequest;
use App\Security\Application\UpdateAccount\UpdateAccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/accounts/edit/{accountId}', name: 'admin_accounts_edit')]
#[IsGranted("ROLE_ADMIN")]
class EditAccountController extends AbstractController
{
    public function __construct(private UpdateAccountService $updateAccount)
    {

    }

    public function __invoke(string $accountId, AccountRepository $accountRepository, Request $request): Response
    {   
        $account = $accountRepository->find(new AccountId($accountId));

        if (null == $account) {
            throw $this->createNotFoundException(new TranslatableMessage("La cuenta no existe"));
        }

        $updateRequest = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive()
        );

        $form = $this->createForm(UpdateAccountType::class, $updateRequest);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountRequest = $form->getData();

            $updateAccount = $this->updateAccount;

            try {
                $updateAccount($accountRequest);

                $this->addFlash('success', new TranslatableMessage('Cuenta actualizada con éxito'));
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha podido actualizar la cuenta'));

                return $this->redirectToRoute('admin_accounts');
            }

            if ($form->getClickedButton() && 'saveAndReturn' == $form->getClickedButton()->getName()) {
                return $this->redirectToRoute('admin_accounts');
            }
        }

        return $this->render('@security/accounts/edit.html.twig', [
            'account' => $account,
            'form' => $form->createView()
        ]);
    }
}
