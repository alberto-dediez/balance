<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury;

use App\Balance\Application\LedgerEntry\Balance\ListLedgerBalancesRequest;
use App\Balance\Application\LedgerEntry\Balance\ListLedgerBalancesService;
use App\Balance\Application\LedgerEntry\Series\ListLedgerSeriesRequest;
use App\Balance\Application\LedgerEntry\Series\ListLedgerSeriesService;
use App\Balance\Application\LedgerEntry\Summary\SummarizeLedgerRequest;
use App\Balance\Application\LedgerEntry\Summary\SummarizeLedgerService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Infrastructure\UI\Web\Admin\Treasury\View\TreasuryDashboardView;
use App\Payments\Domain\Model\Payee\PayeeFilter;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/treasury', name: 'admin_treasury')]
class DashboardController extends AbstractController
{
    use TreasuryPeriodTrait;

    public function __invoke(
        SummarizeLedgerService $summarizeLedger,
        ListLedgerSeriesService $listLedgerSeries,
        ListLedgerBalancesService $listLedgerBalances,
        LedgerEntryRepository $ledgerEntryRepository,
        PayeeRepository $payeeRepository,
        PayoutRepository $payoutRepository,
        PayeeSubjectDirectory $subjectDirectory,
        UrlGeneratorInterface $router,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        list($from, $to) = $this->period($request);

        try {
            $summaryRequest = new SummarizeLedgerRequest(from: $from, to: $to, limit: 0);

            $summary = $summarizeLedger($summaryRequest, $loggedUser());

            $series = $listLedgerSeries(
                new ListLedgerSeriesRequest(from: $from->modify('-11 months'), to: $to),
                $loggedUser(),
            );

            $accountBalances = $listLedgerBalances(new ListLedgerBalancesRequest(LedgerBeneficiary::Account), $loggedUser());
            $projectBalances = $listLedgerBalances(new ListLedgerBalancesRequest(LedgerBeneficiary::Project), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $unassigned = $ledgerEntryRepository->balanceFor(LedgerBeneficiary::Unassigned, null);

        $view = new TreasuryDashboardView(
            summary: $summary,
            series: $series,
            accountBalances: $accountBalances->balances,
            projectBalances: $projectBalances->balances,
            payees: $payeeRepository->findByFilter(new PayeeFilter(limit: 0)),
            failedPayouts: $payoutRepository->findByFilter(new PayoutFilter(status: PayoutStatus::Failed, limit: 0)),
            unassignedBalance: $unassigned,
            unassignedEntries: $unassigned->entriesCount(),
            subjectDirectory: $subjectDirectory,
            router: $router,
            csrfTokenManager: $csrfTokenManager,
        );

        return $this->render('@balance/admin/treasury/dashboard.html.twig', [
            'view' => $view,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
