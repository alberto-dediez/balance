<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury;

use App\Balance\Application\Settlement\Settle\NothingToSettleException;
use App\Balance\Application\Settlement\Settle\SettleLedgerEntriesRequest;
use App\Balance\Application\Settlement\Settle\SettleLedgerEntriesService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Payments\Domain\Model\Payout\PayeeNotReadyException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/treasury/settle/{beneficiary}/{beneficiaryId}', name: 'admin_treasury_settle', methods: ['GET'])]
class SettleController extends AbstractController
{
    public function __invoke(
        string $beneficiary,
        string $beneficiaryId,
        SettleLedgerEntriesService $settleLedgerEntries,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $ledgerBeneficiary = LedgerBeneficiary::tryFrom($beneficiary);

        if (null === $ledgerBeneficiary || !$ledgerBeneficiary->isSettleable()) {
            throw $this->createNotFoundException('Beneficiario no liquidable');
        }

        if (!$this->isCsrfTokenValid(self::csrfTokenId($beneficiary, $beneficiaryId), $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $response = $settleLedgerEntries(
                new SettleLedgerEntriesRequest($ledgerBeneficiary, $beneficiaryId),
                $loggedUser(),
            );

            $this->addFlash('success', new TranslatableMessage(
                'Liquidación {number} creada por {amount} €',
                [
                    '{number}' => (string) $response->number,
                    '{amount}' => number_format($response->amount, 2, ',', '.'),
                ],
            ));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (NothingToSettleException|PayeeNotReadyException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', new TranslatableMessage('El beneficiario no tiene ficha de cobro'));
        }

        return $this->redirectToRoute('admin_payouts');
    }

    public static function csrfTokenId(string $beneficiary, string $beneficiaryId): string
    {
        return sprintf('treasury_settle_%s_%s', $beneficiary, $beneficiaryId);
    }
}
