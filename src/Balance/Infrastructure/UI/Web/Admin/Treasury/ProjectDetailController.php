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
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\UI\Web\Admin\Treasury\View\BeneficiaryDetailView;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/treasury/projects/{projectId}', name: 'admin_treasury_project')]
class ProjectDetailController extends AbstractController
{
    public function __invoke(
        string $projectId,
        ListLedgerEntriesService $listLedgerEntries,
        LedgerEntryRepository $ledgerEntryRepository,
        CreditPurchaseRepository $creditPurchaseRepository,
        ProjectContributionRepository $projectContributionRepository,
        ProjectRepository $projectRepository,
        PayeeRepository $payeeRepository,
        LoggedUser $loggedUser,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new LedgerEntry(id: LedgerEntryId::generate()));

        $project = $projectRepository->find(new ProjectId($projectId));

        if (null === $project) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }

        $filter = new ListLedgerEntriesRequest(limit: 0);
        $filter->projectId = $project->id();
        $filter->orderBy = 'occurredAt';
        $filter->orderWay = 'DESC';

        try {
            $entries = $listLedgerEntries($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $purchases = $creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(
            projectId: $project->id(),
            status: CreditPurchaseStatus::Validated,
            limit: 0,
        ));

        return $this->render('@balance/admin/treasury/project.html.twig', [
            'project' => $project,
            'balance' => $ledgerEntryRepository->balanceFor(LedgerBeneficiary::Project, $projectId),
            'ledgerEntries' => $entries->ledgerEntries,
            'payee' => $payeeRepository->findOneBySubject(PayeeType::Project, $projectId),
            'view' => new BeneficiaryDetailView($purchases, $this->allocationsByPurchase($purchases, $projectContributionRepository)),
            'bioscoreMargin' => $this->bioscoreMargin($ledgerEntryRepository, $project->id()),
        ]);
    }

    /**
     * @param array<\App\Balance\Domain\Model\CreditPurchase\CreditPurchase> $purchases
     *
     * @return array<string, list<ProjectContributionCreditAllocation>>
     */
    private function allocationsByPurchase(array $purchases, ProjectContributionRepository $repository): array
    {
        $allocations = [];

        foreach ($purchases as $purchase) {
            $allocations[(string) $purchase->id()] = $repository->findAllocationsByCreditPurchase($purchase->id());
        }

        return $allocations;
    }

    private function bioscoreMargin(LedgerEntryRepository $repository, ProjectId $projectId): float
    {
        $totals = $repository->totalsByType(new LedgerEntryFilter(
            beneficiary: LedgerBeneficiary::Bioscore,
            projectId: $projectId,
            limit: 0,
        ));

        return $totals[LedgerEntryType::ContributionNet->value] ?? 0.0;
    }
}
