<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Treasury;

use App\Balance\Application\LedgerEntry\Balance\ListLedgerBalancesRequest;
use App\Balance\Application\LedgerEntry\Balance\ListLedgerBalancesService;
use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\UI\Web\Treasury\View\OwnerBalanceView;
use App\Payments\Application\Payout\List\ListPayoutsRequest;
use App\Payments\Application\Payout\List\ListPayoutsService;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/my-balance', name: 'my_balance')]
class BalanceController extends AbstractController
{
    public function __invoke(
        ListLedgerEntriesService $listLedgerEntries,
        ListLedgerBalancesService $listLedgerBalances,
        ListPayoutsService $listPayouts,
        ProjectRepository $projectRepository,
        CreditPurchaseRepository $creditPurchaseRepository,
        PayeeRepository $payeeRepository,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $accountId = $account->id();

        $filter = new ListLedgerEntriesRequest(limit: 0);
        $filter->orderBy = 'occurredAt';
        $filter->orderWay = 'DESC';

        try {
            $entries = $listLedgerEntries($filter, $loggedUser());
            $balances = $listLedgerBalances(new ListLedgerBalancesRequest(LedgerBeneficiary::Account), $loggedUser());
            $payouts = $listPayouts(new ListPayoutsRequest(limit: 0), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/treasury/balance.html.twig', [
            'balance' => $balances->balances[(string) $accountId]
                ?? new LedgerBalance(LedgerBeneficiary::Account, (string) $accountId),
            'view' => new OwnerBalanceView(
                $entries->ledgerEntries,
                $this->projectNames($projectRepository),
                $this->purchaseReferences($creditPurchaseRepository, $accountId),
            ),
            'payouts' => $payouts->payouts,
            'payee' => $payeeRepository->findOneBySubject(PayeeType::Account, (string) $accountId),
        ]);
    }

    /** @return array<string, string> */
    private function projectNames(ProjectRepository $projectRepository): array
    {
        $names = [];

        foreach ($projectRepository->findByFilter(new ProjectFilter(limit: 0)) as $project) {
            $names[(string) $project->id()] = $project->name();
        }

        return $names;
    }

    /** @return array<string, string> */
    private function purchaseReferences(CreditPurchaseRepository $creditPurchaseRepository, AccountId $accountId): array
    {
        $references = [];

        foreach ($creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(accountId: $accountId, limit: 0)) as $purchase) {
            $references[(string) $purchase->id()] = (string) $purchase->reference();
        }

        return $references;
    }
}
