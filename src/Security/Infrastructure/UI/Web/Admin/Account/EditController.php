<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Account\Update\UpdateAccountRequest;
use App\Security\Application\Account\Update\UpdateAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountIsNotConsultantException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\Account\ConsultantCannotHaveConsultantException;
use App\Security\Domain\Model\Account\ConsultantHasAssignedAccountsException;
use App\Security\Infrastructure\Symfony\Form\UpdateAccountType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
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
            consultant: $account->isConsultant(),
            consultantAccountId: $account->consultantAccountId(),
            reportCanEditTexts: $account->reportPermissions()->canEditTexts(),
            reportAutoReview: $account->reportPermissions()->autoReview(),
        );

        $form = $this->createForm(UpdateAccountType::class, $updateRequest);

        $hasConsultantError = false;

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateAccount, $user, &$hasConsultantError): void {
                $accountRequest = $form->getData();

                try {
                    $updateAccount($accountRequest, $user);
                } catch (ConsultantCannotHaveConsultantException|ConsultantHasAssignedAccountsException|AccountIsNotConsultantException $e) {
                    $hasConsultantError = true;

                    $this->addConsultantError($form, $e);

                    return;
                }

                $this->addFlash('success', new TranslatableMessage('Cuenta actualizada con éxito'));
            });

            if ($needsRedirect && !$hasConsultantError) {
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

    /**
     * @param FormInterface<mixed> $form
     */
    private function addConsultantError(FormInterface $form, \RuntimeException $exception): void
    {
        [$field, $message] = match (true) {
            $exception instanceof ConsultantHasAssignedAccountsException => [
                'consultant',
                'No puedes desmarcar esta cuenta como consultora porque tiene cuentas asignadas.',
            ],
            $exception instanceof AccountIsNotConsultantException => [
                'consultantAccountId',
                'La cuenta seleccionada no está marcada como consultora.',
            ],
            default => [
                'consultantAccountId',
                'Una cuenta marcada como consultora no puede tener una consultora asignada.',
            ],
        };

        $form->get($field)->addError(new FormError($message));

        $this->addFlash('danger', new TranslatableMessage('El formulario contiene errores, por favor, reviselos antes de continuar.'));
    }
}
