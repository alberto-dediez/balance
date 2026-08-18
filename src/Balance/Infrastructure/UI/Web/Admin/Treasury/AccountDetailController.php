<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury;

use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\UI\Web\Admin\Treasury\View\BeneficiaryDetailView;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/treasury/accounts/{accountId}', name: 'admin_treasury_account')]
class AccountDetailController extends AbstractController
{
    public function __invoke(
        string $accountId,
        ListLedgerEntriesService $listLedgerEntries,
        LedgerEntryRepository $ledgerEntryRepository,
        CreditPurchaseRepository $creditPurchaseRepository,
        ProjectContributionRepository $projectContributionRepository,
        PayeeRepository $payeeRepository,
        PayoutRepository $payoutRepository,
        LoggedUser $loggedUser,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new LedgerEntry(id: LedgerEntryId::generate()));

        $filter = new ListLedgerEntriesRequest(limit: 0);
        $filter->beneficiary = LedgerBeneficiary::Account;
        $filter->beneficiaryId = $accountId;
        $filter->orderBy = 'occurredAt';
        $filter->orderWay = 'DESC';

        try {
            $entries = $listLedgerEntries($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $payee = $payeeRepository->findOneBySubject(PayeeType::Account, $accountId);

        $purchases = $creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(
            accountId: new AccountId($accountId),
            status: CreditPurchaseStatus::Validated,
            limit: 0,
        ));

        return $this->render('@balance/admin/treasury/account.html.twig', [
            'accountId' => new AccountId($accountId),
            'balance' => $ledgerEntryRepository->balanceFor(LedgerBeneficiary::Account, $accountId),
            'ledgerEntries' => $entries->ledgerEntries,
            'payee' => $payee,
            'view' => new BeneficiaryDetailView($purchases, $projectContributionRepository->findAllocationsByAccount(new AccountId($accountId))),
            'payouts' => null !== $payee
                ? $payoutRepository->findByFilter(new PayoutFilter(payeeId: $payee->id(), limit: 0))
                : [],
        ]);
    }
}
