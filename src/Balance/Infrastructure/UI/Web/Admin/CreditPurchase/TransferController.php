<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\CreditPurchase;

use App\Balance\Application\CreditPurchase\Transfer\TransferableCredit;
use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\InsufficientTransferableCreditException;
use App\Balance\Domain\Model\CreditPurchase\NonTransferableCreditPurchaseException;
use App\Balance\Infrastructure\Symfony\Form\TransferCreditPurchaseType;
use App\Balance\Infrastructure\UI\Web\CreditPurchase\GetCreditPurchaseFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/credits/{creditPurchaseId}/transfer', name: 'admin_credit_purchases_transfer')]
class TransferController extends AbstractController
{
    use GetCreditPurchaseFromIdTrait;
    use EditTrait;

    public function __invoke(
        string $creditPurchaseId,
        CreditPurchaseRepository $creditPurchaseRepository,
        TransferCreditPurchaseService $transferCreditPurchase,
        TransferableCredit $transferableCredit,
        AccountRepository $accountRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $creditPurchase = $this->getCreditPurchaseFromId($creditPurchaseId, $creditPurchaseRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $creditPurchase);

        $transferableTonnes = $transferableCredit->forPurchase($creditPurchase);

        if ($transferableTonnes <= CreditBalance::TONNE_TOLERANCE) {
            $this->addFlash('warning', new TranslatableMessage('Esta compra no tiene crédito disponible para transferir'));

            return $this->redirectToRoute('admin_credit_purchases');
        }

        $transferRequest = new TransferCreditPurchaseRequest(creditPurchaseId: $creditPurchase->id());

        $failed = false;

        try {
            $form = $this->createForm(TransferCreditPurchaseType::class, $transferRequest, [
                'source_account_id' => $creditPurchase->accountId(),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($transferCreditPurchase, $accountRepository, $loggedUser, &$failed) {
                try {
                    $response = $transferCreditPurchase($form->getData(), $loggedUser());
                } catch (NonTransferableCreditPurchaseException|InsufficientTransferableCreditException $e) {
                    $form->addError(new FormError($e->getMessage()));

                    $failed = true;

                    return;
                }

                if (1 === count($response->lots)) {
                    $this->addFlash('success', new TranslatableMessage(
                        'Se han transferido {tonnes} t, de las que {absorbed} t se han aplicado a aportaciones pendientes',
                        [
                            '{tonnes}' => number_format($response->transferredTonnes, 6, ',', '.'),
                            '{absorbed}' => number_format($response->absorbedTonnes, 4, ',', '.'),
                        ],
                    ));
                } else {
                    $this->addFlash('success', new TranslatableMessage(
                        'Se han transferido {tonnes} t a {accounts} cuentas ({share} t por cuenta), de las que {absorbed} t se han aplicado a aportaciones pendientes',
                        [
                            '{tonnes}' => number_format($response->transferredTonnes, 6, ',', '.'),
                            '{accounts}' => count($response->lots),
                            '{share}' => number_format($response->tonnesPerAccount, 6, ',', '.'),
                            '{absorbed}' => number_format($response->absorbedTonnes, 4, ',', '.'),
                        ],
                    ));
                }

                if ($response->retainedTonnes > 0.0) {
                    $this->addFlash('info', new TranslatableMessage(
                        'Quedan {retained} t sin repartir en el lote de origen',
                        ['{retained}' => number_format($response->retainedTonnes, 6, ',', '.')],
                    ));
                }

                $lastLot = end($response->lots);

                if (false !== $lastLot && $response->lastAccountExtraTonnes > 0.0) {
                    $this->addFlash('info', new TranslatableMessage(
                        'La cuenta {account} recibe {extra} t adicionales del resto no divisible',
                        [
                            '{account}' => (string) $accountRepository->find($lastLot->destinationAccountId),
                            '{extra}' => number_format($response->lastAccountExtraTonnes, 6, ',', '.'),
                        ],
                    ));
                }
            });

            if ($needsRedirect && !$failed) {
                return $this->redirectToRoute('admin_credit_purchases');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/credits/transfer.html.twig', [
            'creditPurchase' => $creditPurchase,
            'transferableTonnes' => $transferableTonnes,
            'form' => $form->createView(),
        ]);
    }
}
